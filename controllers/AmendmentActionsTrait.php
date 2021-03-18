<?php

namespace app\controllers;

use app\components\UrlHelper;
use app\models\db\{Amendment, AmendmentAdminComment, AmendmentComment, AmendmentSupporter, ConsultationLog, IComment, Consultation, User};
use app\models\events\AmendmentEvent;
use app\models\exceptions\{DB, FormError, Internal};
use app\models\forms\CommentForm;
use app\models\settings\InitiatorForm;
use app\models\supportTypes\SupportBase;
use yii\web\Response;

/**
 * @property Consultation $consultation
 * @method redirect($uri)
 */
trait AmendmentActionsTrait
{

    /**
     * @throws Internal
     */
    private function getComment(Amendment $amendment, int $commentId, bool $needsScreeningRights): AmendmentComment
    {
        /** @var AmendmentComment $comment */
        $comment = AmendmentComment::findOne($commentId);
        if (!$comment || $comment->amendmentId != $amendment->id || $comment->status != IComment::STATUS_VISIBLE) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if ($needsScreeningRights) {
            if (!User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
                throw new Internal(\Yii::t('comment', 'err_no_screening'));
            }
        }
        return $comment;
    }

    private function writeComment(Amendment $amendment, array &$viewParameters)
    {
        $postComment = \Yii::$app->request->post('comment');

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

        $commentForm = new CommentForm($amendment->getMyMotionType(), $replyTo);
        $commentForm->setAttributes(\Yii::$app->request->getBodyParam('comment'));

        try {
            $commentForm->saveNotificationSettings();
            $comment = $commentForm->saveAmendmentCommentWithChecks($amendment);

            if ($comment->status === AmendmentComment::STATUS_SCREENING) {
                \Yii::$app->session->setFlash('screening', \Yii::t('comment', 'created_needs_screening'));
            } else {
                \Yii::$app->session->setFlash('screening', \Yii::t('comment', 'created'));
            }

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

        \Yii::$app->session->setFlash('success', \Yii::t('comment', 'del_done'));
    }

    /**
     * @throws Internal
     */
    private function screenCommentAccept(Amendment $amendment, int $commentId): void
    {
        /** @var AmendmentComment $comment */
        $comment = AmendmentComment::findOne($commentId);
        if (!$comment || $comment->amendmentId !== $amendment->id) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
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
        /** @var AmendmentComment $comment */
        $comment = AmendmentComment::findOne($commentId);
        if (!$comment || $comment->amendmentId != $amendment->id) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
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
                \Yii::$app->session->setFlash('success', \Yii::t('amend', 'support_already'));
                return;
            }
        }
        $supportClass = $amendment->getMyMotion()->motionType->getAmendmentSupportTypeClass();
        $role = AmendmentSupporter::ROLE_SUPPORTER;
        $user = User::getCurrentUser();
        $gender = \Yii::$app->request->post('motionSupportGender', '');
        $nonPublic = ($supportClass->getSettingsObj()->offerNonPublicSupports && \Yii::$app->request->post('motionSupportPublic') === null);
        if ($user && $user->fixedData) {
            $name = $user->name;
            $orga = $user->organization;
        } else {
            $name = \Yii::$app->request->post('motionSupportName', '');
            $orga = \Yii::$app->request->post('motionSupportOrga', '');
        }
        if ($supportClass->getSettingsObj()->hasOrganizations && trim($orga) === '') {
            \Yii::$app->session->setFlash('error', 'No organization entered');
            return;
        }
        if (trim($name) == '') {
            \Yii::$app->session->setFlash('error', 'You need to enter a name');
            return;
        }
        $validGenderKeys = array_keys(SupportBase::getGenderSelection());
        if ($supportClass->getSettingsObj()->contactGender === InitiatorForm::CONTACT_REQUIRED) {
            if (!in_array($gender, $validGenderKeys)) {
                \Yii::$app->session->setFlash('error', 'You need to fill the gender field');
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
    private function amendmentLikeDislike(Amendment $amendment, string $role, string $string, string $name = '', string $orga = '', string $gender = '', bool $nonPublic = false): void
    {
        $currentUser = User::getCurrentUser();
        if (!$amendment->getMyMotion()->motionType->getAmendmentSupportPolicy()->checkCurrUser()) {
            throw new FormError('Supporting this amendment is not possible');
        }

        AmendmentSupporter::createSupport($amendment, $currentUser, $name, $orga, $role, $gender, $nonPublic);

        \Yii::$app->session->setFlash('success', $string);
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
        $this->amendmentLikeDislike($amendment, AmendmentSupporter::ROLE_LIKE, $msg);
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
        $this->amendmentLikeDislike($amendment, AmendmentSupporter::ROLE_DISLIKE, $msg);
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_DISLIKE, $amendment->id);
    }

    /**
     * @throws FormError
     */
    private function amendmentSupportRevoke(Amendment $amendment): void
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
        $amendment->flushCacheWithChildren(null);
        $consultation = $amendment->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_UNLIKE, $amendment->id);
        \Yii::$app->session->setFlash('success', \Yii::t('amend', 'neutral_done'));
    }

    /**
     * @throws Internal
     */
    private function amendmentSupportFinish(Amendment $amendment): void
    {
        if (!$amendment->canFinishSupportCollection()) {
            \Yii::$app->session->setFlash('error', \Yii::t('amend', 'support_finish_err'));
            return;
        }

        $amendment->trigger(Amendment::EVENT_SUBMITTED, new AmendmentEvent($amendment));

        if ($amendment->status === Amendment::STATUS_SUBMITTED_SCREENED) {
            $amendment->trigger(Amendment::EVENT_PUBLISHED, new AmendmentEvent($amendment));
        }

        $consultation = $amendment->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_SUPPORT_FINISH, $amendment->id);
        \Yii::$app->session->setFlash('success', \Yii::t('amend', 'support_finish_done'));
    }

    private function setProposalAgree(Amendment $amendment): void
    {
        $procedureToken = \Yii::$app->request->get('procedureToken');
        if (!$amendment->canSeeProposedProcedure($procedureToken) || !$amendment->proposalFeedbackHasBeenRequested()) {
            \Yii::$app->session->setFlash('error', 'Not allowed to perform this action');
            return;
        }

        $amendment->proposalUserStatus = Amendment::STATUS_ACCEPTED;
        $amendment->save();
        \Yii::$app->session->setFlash('success', \Yii::t('amend', 'proposal_user_saved'));
    }

    /**
     * @throws \Throwable
     */
    private function savePrivateNote(Amendment $amendment): void
    {
        $user     = User::getCurrentUser();
        $noteText = trim(\Yii::$app->request->post('noteText', ''));
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
        $post = \Yii::$app->request->post();
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
        } elseif (isset($post['writeComment'])) {
            $this->writeComment($amendment, $viewParameters);
        } elseif (isset($post['setProposalAgree'])) {
            $this->setProposalAgree($amendment);
        } elseif (isset($post['savePrivateNote'])) {
            $this->savePrivateNote($amendment);
        }
    }

    /**
     * @param string $motionSlug
     * @param int $amendmentId
     * @return string
     * @throws \Exception
     * @throws \Yii\db\StaleObjectException
     * @throws \Throwable
     */
    public function actionDelProposalComment($motionSlug, $amendmentId)
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        if (!$amendment) {
            return json_encode(['success' => false, 'error' => 'Amendment not found']);
        }

        $commentId = \Yii::$app->request->post('id');
        $comment   = AmendmentAdminComment::findOne(['id' => $commentId, 'amendmentId' => $amendment->id]);
        if ($comment && User::isCurrentUser($comment->getMyUser())) {
            $comment->delete();
            return json_encode(['success' => true]);
        } else {
            return json_encode(['success' => false, 'error' => 'No permission to delete this comment']);
        }
    }
}
