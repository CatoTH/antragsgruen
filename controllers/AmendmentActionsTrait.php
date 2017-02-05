<?php

namespace app\controllers;

use app\components\AntiSpam;
use app\components\EmailNotifications;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\AmendmentSupporter;
use app\models\db\ConsultationLog;
use app\models\db\IComment;
use app\models\db\Consultation;
use app\models\db\User;
use app\models\exceptions\Access;
use app\models\exceptions\DB;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use app\models\forms\CommentForm;
use app\models\supportTypes\ISupportType;

/**
 * @property Consultation $consultation
 * @method redirect($uri)
 */
trait AmendmentActionsTrait
{

    /**
     * @param Amendment $amendment
     * @param int $commentId
     * @param bool $needsScreeningRights
     * @return AmendmentComment
     * @throws Internal
     */
    private function getComment(Amendment $amendment, $commentId, $needsScreeningRights)
    {
        /** @var AmendmentComment $comment */
        $comment = AmendmentComment::findOne($commentId);
        if (!$comment || $comment->amendmentId != $amendment->id || $comment->status != IComment::STATUS_VISIBLE) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if ($needsScreeningRights) {
            if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
                throw new Internal(\Yii::t('comment', 'err_no_screening'));
            }
        }
        return $comment;
    }

    /**
     * @param Amendment $amendment
     * @param array $viewParameters
     * @throws Access
     */
    private function writeComment(Amendment $amendment, &$viewParameters)
    {
        if (!$amendment->getMyMotion()->motionType->getCommentPolicy()->checkCurrUser()) {
            throw new Access('No rights to write a comment');
        }

        $consultation = $amendment->getMyConsultation();
        if (\Yii::$app->user->isGuest) {
            if (AntiSpam::createToken($consultation->id) != \Yii::$app->request->post('jsprotection')) {
                throw new Access(\Yii::t('base', 'err_js_or_login'));
            }
        }
        $commentForm = new CommentForm();
        $commentForm->setAttributes(\Yii::$app->request->getBodyParam('comment'));

        if (User::getCurrentUser()) {
            $commentForm->userId = User::getCurrentUser()->id;
        }

        try {
            $comment = $commentForm->saveAmendmentComment($amendment);
            ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_COMMENT, $comment->id);
            $this->redirect(UrlHelper::createAmendmentCommentUrl($comment));
        } catch (\Exception $e) {
            $viewParameters['commentForm'] = $commentForm;
            if (!isset($viewParameters['openedComments'][$commentForm->sectionId])) {
                $viewParameters['openedComments'][$commentForm->sectionId] = [];
            }
            $viewParameters['openedComments'][$commentForm->sectionId][] = $commentForm->paragraphNo;
            \Yii::$app->session->setFlash('error', $e->getMessage());
        }
    }

    /**
     * @param Amendment $amendment
     * @param int $commentId
     * @throws DB
     * @throws Internal
     */
    private function deleteComment(Amendment $amendment, $commentId)
    {
        $comment = $this->getComment($amendment, $commentId, false);
        if (!$comment->canDelete(User::getCurrentUser())) {
            throw new Internal(\Yii::t('comment', 'err_no_del'));
        }

        $comment->status = IComment::STATUS_DELETED;
        if (!$comment->save(false)) {
            throw new DB($comment->getErrors());
        }

        $consultation = $amendment->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_COMMENT_DELETE, $comment->id);

        \Yii::$app->session->setFlash('success', \Yii::t('comment', 'del_done'));
    }

    /**
     * @param Amendment $amendment
     * @param int $commentId
     * @throws Internal
     */
    private function screenCommentAccept(Amendment $amendment, $commentId)
    {
        /** @var AmendmentComment $comment */
        $comment = AmendmentComment::findOne($commentId);
        if (!$comment || $comment->amendmentId != $amendment->id) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            throw new Internal(\Yii::t('comment', 'err_no_screening'));
        }

        $comment->status = IComment::STATUS_VISIBLE;
        $comment->save();

        $amendment->refresh();

        $consultation = $amendment->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::MOTION_COMMENT_SCREEN, $comment->id);

        $comment->sendPublishNotifications();
    }

    /**
     * @param Amendment $amendment
     * @param int $commentId
     * @throws Internal
     */
    private function screenCommentReject(Amendment $amendment, $commentId)
    {
        /** @var AmendmentComment $comment */
        $comment = AmendmentComment::findOne($commentId);
        if (!$comment || $comment->amendmentId != $amendment->id) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            throw new Internal(\Yii::t('comment', 'err_no_screening'));
        }

        $comment->status = IComment::STATUS_DELETED;
        $comment->save();

        $amendment->refresh();

        $consultation = $amendment->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::MOTION_COMMENT_DELETE, $comment->id);
    }

    /**
     * @param Amendment $amendment
     * @throws FormError
     */
    private function amendmentSupport(Amendment $amendment)
    {
        if (!$amendment->isSupportingPossibleAtThisStatus()) {
            throw new FormError('Not possible given the current amendment status');
        }
        foreach ($amendment->getSupporters() as $supporter) {
            if (User::getCurrentUser() && $supporter->userId == User::getCurrentUser()->id) {
                \Yii::$app->session->setFlash('success', \Yii::t('amend', 'support_already'));
                return;
            }
        }
        $supportClass = $amendment->getMyMotion()->motionType->getAmendmentSupportTypeClass();
        $role         = AmendmentSupporter::ROLE_SUPPORTER;
        $user         = User::getCurrentUser();
        if ($user && $user->fixedData) {
            $name = $user->name;
            $orga = $user->organization;
        } else {
            $name = \Yii::$app->request->post('motionSupportName', '');
            $orga = \Yii::$app->request->post('motionSupportOrga', '');
        }
        if ($supportClass->hasOrganizations() && $orga == '') {
            \Yii::$app->session->setFlash('error', 'No organization entered');
            return;
        }
        if (trim($name) == '') {
            \Yii::$app->session->setFlash('error', 'You need to enter a name');
            return;
        }

        $this->amendmentLikeDislike($amendment, $role, \Yii::t('amend', 'support_done'), $name, $orga);
        ConsultationLog::logCurrUser($amendment->getMyConsultation(), ConsultationLog::MOTION_SUPPORT, $amendment->id);

        $minSupporters = $supportClass->getMinNumberOfSupporters();
        if (count($amendment->getSupporters()) == $minSupporters) {
            EmailNotifications::sendAmendmentSupporterMinimumReached($amendment);
        }
    }

    /**
     * @param Amendment $amendment
     * @param string $role
     * @param string $string
     * @param string $name
     * @param string $orga
     * @throws FormError
     */
    private function amendmentLikeDislike(Amendment $amendment, $role, $string, $name = '', $orga = '')
    {
        $currentUser = User::getCurrentUser();
        if (!$amendment->getMyMotion()->motionType->getAmendmentSupportPolicy()->checkCurrUser()) {
            throw new FormError('Supporting this amendment is not possible');
        }

        AmendmentSupporter::createSupport($amendment, $currentUser, $name, $orga, $role);

        $amendment->refresh();

        \Yii::$app->session->setFlash('success', $string);
    }

    /**
     * @param Amendment $amendment
     * @throws FormError
     */
    private function amendmentLike(Amendment $amendment)
    {
        if (!($amendment->getLikeDislikeSettings() & ISupportType::LIKEDISLIKE_LIKE)) {
            throw new FormError('Not supported');
        }
        $msg = \Yii::t('amend', 'like_done');
        $this->amendmentLikeDislike($amendment, AmendmentSupporter::ROLE_LIKE, $msg);
        ConsultationLog::logCurrUser($amendment->getMyConsultation(), ConsultationLog::AMENDMENT_LIKE, $amendment->id);
    }

    /**
     * @param Amendment $amendment
     * @throws FormError
     */
    private function amendmentDislike(Amendment $amendment)
    {
        if (!($amendment->getLikeDislikeSettings() & ISupportType::LIKEDISLIKE_DISLIKE)) {
            throw new FormError('Not supported');
        }
        $msg          = \Yii::t('amend', 'dislike_done');
        $consultation = $amendment->getMyConsultation();
        $this->amendmentLikeDislike($amendment, AmendmentSupporter::ROLE_DISLIKE, $msg);
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_DISLIKE, $amendment->id);
    }

    /**
     * @param Amendment $amendment
     * @throws FormError
     */
    private function amendmentSupportRevoke(Amendment $amendment)
    {
        $currentUser          = User::getCurrentUser();
        $anonymouslySupported = AmendmentSupporter::getMyAnonymousSupportIds();
        foreach ($amendment->amendmentSupporters as $supp) {
            if (($currentUser && $supp->userId == $currentUser->id) || in_array($supp->id, $anonymouslySupported)) {
                if ($supp->role == AmendmentSupporter::ROLE_SUPPORTER) {
                    if (!$amendment->isSupportingPossibleAtThisStatus()) {
                        throw new FormError('Not possible given the current amendment status');
                    }
                }
                $amendment->unlink('amendmentSupporters', $supp, true);
            }
        }
        $consultation = $amendment->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_UNLIKE, $amendment->id);
        \Yii::$app->session->setFlash('success', \Yii::t('amend', 'neutral_done'));
    }

    /**
     * @param Amendment $amendment
     */
    private function amendmentSupportFinish(Amendment $amendment)
    {
        if (!$amendment->canFinishSupportCollection()) {
            \Yii::$app->session->setFlash('error', \Yii::t('amend', 'support_finish_err'));
            return;
        }

        $amendment->setInitialSubmitted();

        if ($amendment->status == Amendment::STATUS_SUBMITTED_SCREENED) {
            $amendment->onPublish();
        } else {
            EmailNotifications::sendAmendmentSubmissionConfirm($amendment);
        }

        $consultation = $amendment->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::MOTION_SUPPORT_FINISH, $amendment->id);
        \Yii::$app->session->setFlash('success', \Yii::t('amend', 'support_finish_done'));
    }

    /**
     * @param Amendment $amendment
     * @param int $commentId
     * @param array $viewParameters
     */
    private function performShowActions(Amendment $amendment, $commentId, &$viewParameters)
    {
        $post = \Yii::$app->request->post();
        if ($commentId == 0 && isset($post['commentId'])) {
            $commentId = IntVal($post['commentId']);
        }
        if (isset($post['deleteComment'])) {
            $this->deleteComment($amendment, $commentId);
        } elseif (isset($post['commentScreeningAccept'])) {
            $this->screenCommentAccept($amendment, $commentId);

        } elseif (isset($post['commentScreeningReject'])) {
            $this->screenCommentReject($amendment, $commentId);

        } elseif (isset($post['motionLike'])) {
            $this->amendmentLike($amendment);

        } elseif (isset($post['motionDislike'])) {
            $this->amendmentDislike($amendment);

        } elseif (isset($post['motionSupport'])) {
            $this->amendmentSupport($amendment);

        } elseif (isset($post['motionSupportRevoke'])) {
            $this->amendmentSupportRevoke($amendment);

        } elseif (isset($post['amendmentSupportFinish'])) {
            $this->amendmentSupportFinish($amendment);

        } elseif (isset($post['writeComment'])) {
            $this->writeComment($amendment, $viewParameters);
        }
    }
}
