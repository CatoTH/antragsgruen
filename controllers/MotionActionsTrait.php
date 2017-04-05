<?php

namespace app\controllers;

use app\components\AntiSpam;
use app\components\UrlHelper;
use app\models\db\ConsultationLog;
use app\models\db\IComment;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\db\Consultation;
use app\models\exceptions\Access;
use app\models\exceptions\DB;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use app\models\forms\CommentForm;
use app\models\supportTypes\ISupportType;
use app\components\EmailNotifications;

/**
 * @property Consultation $consultation
 * @method redirect($uri)
 */
trait MotionActionsTrait
{
    /**
     * @param Motion $motion
     * @param int $commentId
     * @param bool $needsScreeningRights
     * @return MotionComment
     * @throws Internal
     */
    private function getComment(Motion $motion, $commentId, $needsScreeningRights)
    {
        /** @var MotionComment $comment */
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId != $motion->id || $comment->status != IComment::STATUS_VISIBLE) {
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
     * @param Motion $motion
     * @param array $viewParameters
     * @throws Access
     */
    private function writeComment(Motion $motion, &$viewParameters)
    {
        if (!$motion->motionType->getCommentPolicy()->checkCurrUser()) {
            throw new Access('No rights to write a comment');
        }
        if (\Yii::$app->user->isGuest) {
            if (AntiSpam::createToken($motion->consultationId) != \Yii::$app->request->post('jsprotection')) {
                throw new Access(\Yii::t('base', 'err_js_or_login'));
            }
        }
        $postComment = \Yii::$app->request->post('comment');
        $commentForm = new CommentForm();
        $commentForm->setAttributes($postComment);
        $commentForm->sectionId = null;
        if ($postComment['sectionId'] > 0) {
            foreach ($motion->getActiveSections() as $section) {
                if ($section->sectionId == $postComment['sectionId']) {
                    $commentForm->sectionId = $postComment['sectionId'];
                }
            }
        }

        if (User::getCurrentUser()) {
            $commentForm->userId = User::getCurrentUser()->id;
        }

        try {
            $comment = $commentForm->saveMotionComment($motion);
            ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_COMMENT, $comment->id);
            if ($comment->status == MotionComment::STATUS_SCREENING) {
                \yii::$app->session->setFlash('screening', \Yii::t('comment', 'created_needs_screening'));
            } else {
                \yii::$app->session->setFlash('screening', \Yii::t('comment', 'created'));
            }
            $this->redirect(UrlHelper::createMotionCommentUrl($comment));
            \yii::$app->end();
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
     * @param Motion $motion
     * @param int $commentId
     * @throws DB
     * @throws Internal
     */
    private function deleteComment(Motion $motion, $commentId)
    {
        $comment = $this->getComment($motion, $commentId, false);
        if (!$comment->canDelete(User::getCurrentUser())) {
            throw new Internal(\Yii::t('comment', 'err_no_del'));
        }

        $comment->status = IComment::STATUS_DELETED;
        if (!$comment->save(false)) {
            throw new DB($comment->getErrors());
        }
        ConsultationLog::logCurrUser(
            $motion->getMyConsultation(),
            ConsultationLog::MOTION_COMMENT_DELETE,
            $comment->id
        );

        \Yii::$app->session->setFlash('success', \Yii::t('comment', 'del_done'));
    }

    /**
     * @param Motion $motion
     * @param int $commentId
     * @throws Internal
     */
    private function screenCommentAccept(Motion $motion, $commentId)
    {
        /** @var MotionComment $comment */
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId != $motion->id) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            throw new Internal(\Yii::t('comment', 'err_no_screening'));
        }

        $comment->status = IComment::STATUS_VISIBLE;
        $comment->save();

        $motion->refresh();

        ConsultationLog::logCurrUser(
            $motion->getMyConsultation(),
            ConsultationLog::MOTION_COMMENT_SCREEN,
            $comment->id
        );

        $comment->sendPublishNotifications();
    }

    /**
     * @param Motion $motion
     * @param int $commentId
     * @throws Internal
     */
    private function screenCommentReject(Motion $motion, $commentId)
    {
        /** @var MotionComment $comment */
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId != $motion->id) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            throw new Internal(\Yii::t('comment', 'err_no_screening'));
        }

        $comment->status = IComment::STATUS_DELETED;
        $comment->save();

        $motion->refresh();

        ConsultationLog::logCurrUser(
            $motion->getMyConsultation(),
            ConsultationLog::MOTION_COMMENT_DELETE,
            $comment->id
        );
    }

    /**
     * @param Motion $motion
     * @param string $role
     * @param string $string
     * @param string $name
     * @param string $orga
     * @throws FormError
     */
    private function motionLikeDislike(Motion $motion, $role, $string, $name = '', $orga = '')
    {
        $currentUser = User::getCurrentUser();
        if (!$motion->motionType->getMotionSupportPolicy()->checkCurrUser()) {
            throw new FormError('Supporting this motion is not possible');
        }

        MotionSupporter::createSupport($motion, $currentUser, $name, $orga, $role);

        $motion->refresh();

        \Yii::$app->session->setFlash('success', $string);
    }

    /**
     * @param Motion $motion
     * @throws FormError
     */
    private function motionLike(Motion $motion)
    {
        if (!($motion->getLikeDislikeSettings() & ISupportType::LIKEDISLIKE_LIKE)) {
            throw new FormError('Not supported');
        }
        $this->motionLikeDislike($motion, MotionSupporter::ROLE_LIKE, \Yii::t('motion', 'like_done'));
        ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_LIKE, $motion->id);
    }

    /**
     * @param Motion $motion
     * @throws FormError
     */
    private function motionSupport(Motion $motion)
    {
        if (!$motion->isSupportingPossibleAtThisStatus()) {
            throw new FormError('Not possible given the current motion status');
        }
        foreach ($motion->getSupporters() as $supporter) {
            if (User::getCurrentUser() && $supporter->userId == User::getCurrentUser()->id) {
                \Yii::$app->session->setFlash('success', \Yii::t('motion', 'support_already'));
                return;
            }
        }
        $supportType = $motion->motionType->getMotionSupportTypeClass();
        $role        = MotionSupporter::ROLE_SUPPORTER;
        $user        = User::getCurrentUser();
        if ($user && $user->fixedData) {
            $name = $user->name;
            $orga = $user->organization;
        } else {
            $name = \Yii::$app->request->post('motionSupportName', '');
            $orga = \Yii::$app->request->post('motionSupportOrga', '');
        }
        if ($supportType->hasOrganizations() && $orga == '') {
            \Yii::$app->session->setFlash('error', 'No organization entered');
            return;
        }
        if (trim($name) == '') {
            \Yii::$app->session->setFlash('error', 'You need to enter a name');
            return;
        }

        $this->motionLikeDislike($motion, $role, \Yii::t('motion', 'support_done'), $name, $orga);
        ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_SUPPORT, $motion->id);

        if (count($motion->getSupporters()) == $supportType->getMinNumberOfSupporters()) {
            EmailNotifications::sendMotionSupporterMinimumReached($motion);
        }
    }

    /**
     * @param Motion $motion
     * @throws FormError
     */
    private function motionDislike(Motion $motion)
    {
        if (!($motion->getLikeDislikeSettings() & ISupportType::LIKEDISLIKE_DISLIKE)) {
            throw new FormError('Not supported');
        }
        $this->motionLikeDislike($motion, MotionSupporter::ROLE_DISLIKE, \Yii::t('motion', 'dislike_done'));
        ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_DISLIKE, $motion->id);
    }

    /**
     * @param Motion $motion
     * @throws FormError
     */
    private function motionSupportRevoke(Motion $motion)
    {
        $currentUser          = User::getCurrentUser();
        $anonymouslySupported = MotionSupporter::getMyAnonymousSupportIds();
        foreach ($motion->motionSupporters as $supp) {
            if (($currentUser && $supp->userId == $currentUser->id) || in_array($supp->id, $anonymouslySupported)) {
                if ($supp->role == MotionSupporter::ROLE_SUPPORTER) {
                    if (!$motion->isSupportingPossibleAtThisStatus()) {
                        throw new FormError('Not possible given the current motion status');
                    }
                }
                $motion->unlink('motionSupporters', $supp, true);
            }
        }
        ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_UNLIKE, $motion->id);
        \Yii::$app->session->setFlash('success', \Yii::t('motion', 'neutral_done'));
    }

    /**
     * @param Motion $motion
     */
    private function motionSupportFinish(Motion $motion)
    {
        if (!$motion->canFinishSupportCollection()) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'support_finish_err'));
            return;
        }

        $motion->setInitialSubmitted();

        if ($motion->status == Motion::STATUS_SUBMITTED_SCREENED) {
            $motion->onPublish();
        } else {
            EmailNotifications::sendMotionSubmissionConfirm($motion);
        }

        ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_SUPPORT_FINISH, $motion->id);
        \Yii::$app->session->setFlash('success', \Yii::t('motion', 'support_finish_done'));
    }

    /**
     * @param Motion $motion
     * @throws Internal
     */
    private function motionAddTag(Motion $motion)
    {
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            throw new Internal(\Yii::t('comment', 'err_no_screening'));
        }
        foreach ($motion->getMyConsultation()->tags as $tag) {
            if ($tag->id == \Yii::$app->request->post('tagId')) {
                $motion->link('tags', $tag);
            }
        }
    }

    /**
     * @param Motion $motion
     * @throws Internal
     */
    private function motionDelTag(Motion $motion)
    {
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            throw new Internal(\Yii::t('comment', 'err_no_screening'));
        }
        foreach ($motion->getMyConsultation()->tags as $tag) {
            if ($tag->id == \Yii::$app->request->post('tagId')) {
                $motion->unlink('tags', $tag, true);
            }
        }
    }

    /**
     * @param Motion $motion
     * @param int $commentId
     * @param array $viewParameters
     */
    private function performShowActions(Motion $motion, $commentId, &$viewParameters)
    {
        $post = \Yii::$app->request->post();
        if ($commentId == 0 && isset($post['commentId'])) {
            $commentId = IntVal($post['commentId']);
        }
        if (isset($post['deleteComment'])) {
            $this->deleteComment($motion, $commentId);

        } elseif (isset($post['commentScreeningAccept'])) {
            $this->screenCommentAccept($motion, $commentId);

        } elseif (isset($post['commentScreeningReject'])) {
            $this->screenCommentReject($motion, $commentId);

        } elseif (isset($post['motionLike'])) {
            $this->motionLike($motion);

        } elseif (isset($post['motionDislike'])) {
            $this->motionDislike($motion);

        } elseif (isset($post['motionSupport'])) {
            $this->motionSupport($motion);

        } elseif (isset($post['motionSupportRevoke'])) {
            $this->motionSupportRevoke($motion);

        } elseif (isset($post['motionSupportFinish'])) {
            $this->motionSupportFinish($motion);

        } elseif (isset($post['motionAddTag'])) {
            $this->motionAddTag($motion);

        } elseif (isset($post['motionDelTag'])) {
            $this->motionDelTag($motion);

        } elseif (isset($post['writeComment'])) {
            $this->writeComment($motion, $viewParameters);
        }
    }
}
