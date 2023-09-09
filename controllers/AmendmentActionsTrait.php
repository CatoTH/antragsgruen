<?php

namespace app\controllers;

use app\components\{RequestContext, UrlHelper};
use app\models\db\{Amendment, AmendmentAdminComment, AmendmentComment, AmendmentSupporter, ConsultationLog, ConsultationSettingsTag, IComment, Consultation, User};
use app\models\events\AmendmentEvent;
use app\models\http\{JsonResponse, RedirectResponse};
use app\models\settings\{PrivilegeQueryContext, Privileges, InitiatorForm};
use app\models\exceptions\{DB, FormError, Internal, ResponseException};
use app\models\forms\CommentForm;
use app\models\supportTypes\SupportBase;
use yii\web\{Request, Session};

/**
 * @property Consultation $consultation
 * @method Session getHttpSession()
 * @method Request getHttpRequest()
 */
trait AmendmentActionsTrait
{
    /**
     * @throws Internal
     */
    private function getComment(Amendment $amendment, int $commentId, bool $needsScreeningRights): AmendmentComment
    {
        /** @var AmendmentComment|null $comment */
        $comment = AmendmentComment::findOne($commentId);
        if (!$comment || $comment->amendmentId !== $amendment->id || $comment->status !== IComment::STATUS_VISIBLE) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if ($needsScreeningRights) {
            if (!$this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, null)) {
                throw new Internal(\Yii::t('comment', 'err_no_screening'));
            }
        }
        return $comment;
    }

    private function writeComment(Amendment $amendment, array &$viewParameters): void
    {
        $postComment = $this->getPostValue('comment');

        $replyTo = null;
        if (isset($postComment['parentCommentId']) && $postComment['parentCommentId']) {
            $replyTo = AmendmentComment::findOne([
                'id'              => $postComment['parentCommentId'],
                'amendmentId'     => $amendment->id,
                'parentCommentId' => null,
            ]);
            if ($replyTo && $replyTo->status === IComment::STATUS_DELETED) {
                $replyTo = null;
            }
        }

        $commentForm = new CommentForm($amendment, $replyTo);
        $commentForm->setAttributes($this->getPostValue('comment'));
        $redirectUrl = null;

        try {
            $commentForm->saveNotificationSettings();
            $comment = $commentForm->saveAmendmentCommentWithChecks($amendment);

            if ($comment->status === AmendmentComment::STATUS_SCREENING) {
                $this->getHttpSession()->setFlash('screening', \Yii::t('comment', 'created_needs_screening'));
            } else {
                $this->getHttpSession()->setFlash('screening', \Yii::t('comment', 'created'));
            }

            $redirectUrl = UrlHelper::createAmendmentCommentUrl($comment);
        } catch (\Exception $e) {
            $viewParameters['commentForm'] = $commentForm;
            if (!isset($viewParameters['openedComments'][$commentForm->sectionId])) {
                $viewParameters['openedComments'][$commentForm->sectionId] = [];
            }
            $viewParameters['openedComments'][$commentForm->sectionId][] = $commentForm->paragraphNo;
            $this->getHttpSession()->setFlash('error', $e->getMessage());
        }

        if ($redirectUrl) {
            throw new ResponseException(new RedirectResponse($redirectUrl));
        }
    }



    /**
     * @throws Internal
     */
    private function amendmentAddTag(Amendment $amendment): void
    {
        if (!$this->consultation->havePrivilege(Privileges::PRIVILEGE_MOTION_STATUS_EDIT, PrivilegeQueryContext::amendment($amendment))) {
            throw new Internal(\Yii::t('comment', 'err_no_screening'));
        }
        foreach ($amendment->getMyConsultation()->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
            if ($tag->id === intval($this->getHttpRequest()->post('tagId'))) {
                $amendment->link('tags', $tag);
            }
        }
    }

    /**
     * @throws Internal
     */
    private function amendmentDelTag(Amendment $amendment): void
    {
        if (!$this->consultation->havePrivilege(Privileges::PRIVILEGE_MOTION_STATUS_EDIT, PrivilegeQueryContext::amendment($amendment))) {
            throw new Internal(\Yii::t('comment', 'err_no_screening'));
        }
        foreach ($amendment->getMyConsultation()->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
            if ($tag->id === intval($this->getHttpRequest()->post('tagId'))) {
                $amendment->unlink('tags', $tag, true);
            }
        }
    }

    /**
     * @throws DB
     * @throws Internal
     */
    private function deleteComment(Amendment $amendment, int $commentId): void
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

        $this->getHttpSession()->setFlash('success', \Yii::t('comment', 'del_done'));
    }

    /**
     * @throws Internal
     */
    private function screenCommentAccept(Amendment $amendment, int $commentId): void
    {
        /** @var AmendmentComment|null $comment */
        $comment = AmendmentComment::findOne($commentId);
        if (!$comment || $comment->amendmentId !== $amendment->id) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if (!$this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::amendment($amendment))) {
            throw new Internal(\Yii::t('comment', 'err_no_screening'));
        }

        $comment->status = IComment::STATUS_VISIBLE;
        $comment->save();

        $amendment->refresh();

        $comment->trigger(IComment::EVENT_PUBLISHED);
    }

    /**
     * @throws Internal
     */
    private function screenCommentReject(Amendment $amendment, int $commentId): void
    {
        /** @var AmendmentComment|null $comment */
        $comment = AmendmentComment::findOne($commentId);
        if (!$comment || $comment->amendmentId !== $amendment->id) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if (!$this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, null)) {
            throw new Internal(\Yii::t('comment', 'err_no_screening'));
        }

        $comment->status = IComment::STATUS_DELETED;
        $comment->save();

        $amendment->refresh();

        $consultation = $amendment->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::MOTION_COMMENT_DELETE, $comment->id);
    }

    /**
     * @throws FormError
     */
    private function amendmentSupport(Amendment $amendment): void
    {
        if (!$amendment->isSupportingPossibleAtThisStatus()) {
            throw new FormError('Not possible given the current amendment status');
        }
        foreach ($amendment->getSupporters(true) as $supporter) {
            if (User::getCurrentUser() && $supporter->userId == User::getCurrentUser()->id) {
                $this->getHttpSession()->setFlash('success', \Yii::t('amend', 'support_already'));
                return;
            }
        }
        $supportClass = $amendment->getMyMotion()->motionType->getAmendmentSupportTypeClass();
        $role = AmendmentSupporter::ROLE_SUPPORTER;
        $user = User::getCurrentUser();
        $gender = $this->getHttpRequest()->post('motionSupportGender', '');
        $nonPublic = ($supportClass->getSettingsObj()->offerNonPublicSupports && RequestContext::getWebRequest()->post('motionSupportPublic') === null);
        if ($user && ($user->fixedData & User::FIXED_NAME)) {
            $name = $user->name;
        } else {
            $name = $this->getHttpRequest()->post('motionSupportName', '');
        }
        if ($user && ($user->fixedData & User::FIXED_ORGA)) {
            $orga = $user->organization;
        } else {
            $orga = $this->getHttpRequest()->post('motionSupportOrga', '');
        }
        if ($supportClass->getSettingsObj()->hasOrganizations && trim($orga) === '') {
            $this->getHttpSession()->setFlash('error', 'No organization entered');
            return;
        }
        if (trim($name) === '') {
            $this->getHttpSession()->setFlash('error', 'You need to enter a name');
            return;
        }
        $validGenderKeys = array_keys(SupportBase::getGenderSelection());
        if ($supportClass->getSettingsObj()->contactGender === InitiatorForm::CONTACT_REQUIRED) {
            if (!in_array($gender, $validGenderKeys)) {
                $this->getHttpSession()->setFlash('error', 'You need to fill the gender field');
                return;
            }
        }
        if (!in_array($gender, $validGenderKeys)) {
            $gender = '';
        }

        $this->amendmentLikeDislike($amendment, $role, \Yii::t('amend', 'support_done'), $name, $orga, $gender, $nonPublic);
        ConsultationLog::logCurrUser($amendment->getMyConsultation(), ConsultationLog::AMENDMENT_SUPPORT, $amendment->id);
    }

    /**
     * @throws FormError
     * @throws Internal
     */
    private function amendmentLikeDislike(Amendment $amendment, string $role, string $string, string $name, string $orga = '', string $gender = '', bool $nonPublic = false): void
    {
        $currentUser = User::getCurrentUser();
        if (!$amendment->getMyMotion()->motionType->getAmendmentSupportPolicy()->checkCurrUser()) {
            throw new FormError('Supporting this amendment is not possible');
        }

        AmendmentSupporter::createSupport($amendment, $currentUser, $name, $orga, $role, $gender, $nonPublic);

        $this->getHttpSession()->setFlash('success', $string);
    }

    /**
     * @throws FormError
     */
    private function amendmentLike(Amendment $amendment): void
    {
        if (!($amendment->getLikeDislikeSettings() & SupportBase::LIKEDISLIKE_LIKE)) {
            throw new FormError('Not supported');
        }
        $msg = \Yii::t('amend', 'like_done');
        $name = (User::getCurrentUser() ? '' : $this->getHttpRequest()->post('likeName', ''));

        $this->amendmentLikeDislike($amendment, AmendmentSupporter::ROLE_LIKE, $msg, $name);
        ConsultationLog::logCurrUser($amendment->getMyConsultation(), ConsultationLog::AMENDMENT_LIKE, $amendment->id);
    }

    /**
     * @throws FormError
     */
    private function amendmentDislike(Amendment $amendment): void
    {
        if (!($amendment->getLikeDislikeSettings() & SupportBase::LIKEDISLIKE_DISLIKE)) {
            throw new FormError('Not supported');
        }
        $msg          = \Yii::t('amend', 'dislike_done');
        $consultation = $amendment->getMyConsultation();

        $name = (User::getCurrentUser() ? '' : $this->getHttpRequest()->post('likeName', ''));

        $this->amendmentLikeDislike($amendment, AmendmentSupporter::ROLE_DISLIKE, $msg, $name);
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_DISLIKE, $amendment->id);
    }

    /**
     * @throws FormError
     */
    private function amendmentSupportRevoke(Amendment $amendment): void
    {
        $currentUser          = User::getCurrentUser();
        $loginlessSupported = AmendmentSupporter::getMyLoginlessSupportIds();
        foreach ($amendment->amendmentSupporters as $supp) {
            if (($currentUser && $supp->userId == $currentUser->id) || in_array($supp->id, $loginlessSupported)) {
                if ($supp->role == AmendmentSupporter::ROLE_SUPPORTER) {
                    if (!$amendment->isSupportingPossibleAtThisStatus()) {
                        throw new FormError('Not possible given the current amendment status');
                    }
                }
                $amendment->unlink('amendmentSupporters', $supp, true);
            }
        }
        $amendment->flushCacheWithChildren(null);
        $consultation = $amendment->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_UNLIKE, $amendment->id);
        $this->getHttpSession()->setFlash('success', \Yii::t('amend', 'neutral_done'));
    }

    /**
     * @throws Internal
     */
    private function amendmentSupportFinish(Amendment $amendment): void
    {
        if (!$amendment->canFinishSupportCollection()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('amend', 'support_finish_err'));
            return;
        }

        $amendment->trigger(Amendment::EVENT_SUBMITTED, new AmendmentEvent($amendment));

        if ($amendment->status === Amendment::STATUS_SUBMITTED_SCREENED) {
            $amendment->trigger(Amendment::EVENT_PUBLISHED, new AmendmentEvent($amendment));
        }

        $consultation = $amendment->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_SUPPORT_FINISH, $amendment->id);
        $this->getHttpSession()->setFlash('success', \Yii::t('amend', 'support_finish_done'));
    }

    private function setProposalAgree(Amendment $amendment): void
    {
        $procedureToken = RequestContext::getWebRequest()->get('procedureToken');
        if (!$amendment->canSeeProposedProcedure($procedureToken) || !$amendment->proposalFeedbackHasBeenRequested()) {
            $this->getHttpSession()->setFlash('error', 'Not allowed to perform this action');
            return;
        }

        $amendment->proposalUserStatus = Amendment::STATUS_ACCEPTED;
        $amendment->save();
        $this->getHttpSession()->setFlash('success', \Yii::t('amend', 'proposal_user_saved'));
    }

    /**
     * @throws \Throwable
     */
    private function savePrivateNote(Amendment $amendment): void
    {
        $user     = User::getCurrentUser();
        $noteText = trim(RequestContext::getWebRequest()->post('noteText', ''));
        if (!$user) {
            return;
        }

        $comment = $amendment->getPrivateComment();
        if ($comment && $noteText === '') {
            $comment->delete();
            $amendment->refresh();
            return;
        }

        if (!$comment) {
            $comment = new AmendmentComment();
        }
        $comment->amendmentId  = $amendment->id;
        $comment->userId       = $user->id;
        $comment->text         = $noteText;
        $comment->status       = IComment::STATUS_PRIVATE;
        $comment->paragraph    = 0;
        $comment->dateCreation = date('Y-m-d H:i:s');
        $comment->name         = ($user->name ? $user->name : '-');
        $comment->save();

        $amendment->refresh();
    }

    /**
     * @throws \Throwable
     */
    private function performShowActions(Amendment $amendment, int $commentId, array &$viewParameters): void
    {
        $post = RequestContext::getWebRequest()->post();
        if ($commentId === 0 && isset($post['commentId'])) {
            $commentId = intval($post['commentId']);
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
        } elseif (isset($post['addTag'])) {
            $this->amendmentAddTag($amendment);
        } elseif (isset($post['delTag'])) {
            $this->amendmentDelTag($amendment);
        } elseif (isset($post['writeComment'])) {
            $this->writeComment($amendment, $viewParameters);
        } elseif (isset($post['setProposalAgree'])) {
            $this->setProposalAgree($amendment);
        } elseif (isset($post['savePrivateNote'])) {
            $this->savePrivateNote($amendment);
        }
    }

    public function actionDelProposalComment(string $motionSlug, int $amendmentId): JsonResponse
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            return new JsonResponse(['success' => false, 'error' => 'Amendment not found']);
        }

        $commentId = (int)$this->getPostValue('id');
        $comment   = AmendmentAdminComment::findOne(['id' => $commentId, 'amendmentId' => $amendment->id]);
        if ($comment && User::isCurrentUser($comment->getMyUser())) {
            $comment->delete();
            return new JsonResponse(['success' => true]);
        } else {
            return new JsonResponse(['success' => false, 'error' => 'No permission to delete this comment']);
        }
    }
}
