<?php

namespace app\controllers;

use app\models\consultationLog\ProposedProcedureChange;
use app\models\forms\ProposedChangeForm;
use app\models\settings\{AntragsgruenApp, PrivilegeQueryContext, Privileges, InitiatorForm};
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextEditorial;
use app\models\http\{HtmlErrorResponse,
    HtmlResponse,
    JsonResponse,
    RedirectResponse,
    ResponseInterface,
    RestApiExceptionResponse};
use app\models\notifications\MotionProposedProcedure;
use app\components\{Tools, UrlHelper};
use app\models\db\{Amendment,
    ConsultationLog,
    ConsultationSettingsTag,
    IComment,
    IMotion,
    Motion,
    MotionAdminComment,
    MotionComment,
    MotionSupporter,
    User,
    Consultation};
use app\models\exceptions\{DB, FormError, Internal, MailNotSent, ResponseException};
use app\models\forms\CommentForm;
use app\models\events\MotionEvent;
use app\models\supportTypes\SupportBase;
use yii\web\{Request, Response, Session};

/**
 * @property Consultation $consultation
 * @method Session getHttpSession()
 * @method Request getHttpRequest()
 * @method Response getHttpResponse()
 */
trait MotionActionsTrait
{
    /**
     * @throws Internal
     */
    private function getComment(Motion $motion, int $commentId, bool $needsScreeningRights): MotionComment
    {
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId != $motion->id || $comment->status != IComment::STATUS_VISIBLE) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if ($needsScreeningRights) {
            if (!$this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, null)) {
                throw new Internal(\Yii::t('comment', 'err_no_screening'));
            }
        }
        return $comment;
    }

    private function writeComment(Motion $motion, array &$viewParameters): void
    {
        $postComment = $this->getHttpRequest()->post('comment');

        $replyTo = null;
        if (isset($postComment['parentCommentId']) && $postComment['parentCommentId']) {
            $replyTo = MotionComment::findOne([
                'id'              => $postComment['parentCommentId'],
                'motionId'        => $motion->id,
                'parentCommentId' => null,
            ]);
            if ($replyTo && $replyTo->status === IComment::STATUS_DELETED) {
                $replyTo = null;
            }
        }

        $commentForm = new CommentForm($motion, $replyTo);
        $commentForm->setAttributes($postComment, $motion->getActiveSections());
        $redirectUrl = null;

        try {
            $commentForm->saveNotificationSettings();
            $comment = $commentForm->saveMotionCommentWithChecks($motion);

            if ($comment->status === MotionComment::STATUS_SCREENING) {
                $this->getHttpSession()->setFlash('screening', \Yii::t('comment', 'created_needs_screening'));
            } else {
                $this->getHttpSession()->setFlash('screening', \Yii::t('comment', 'created'));
            }

            $redirectUrl = UrlHelper::createMotionCommentUrl($comment);
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
     * @throws DB
     * @throws Internal
     */
    private function deleteComment(Motion $motion, int $commentId): void
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

        $this->getHttpSession()->setFlash('success', \Yii::t('comment', 'del_done'));
    }

    /**
     * @throws Internal
     */
    private function screenCommentAccept(Motion $motion, int $commentId): void
    {
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId !== $motion->id) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if (!$this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, null)) {
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

        $comment->trigger(IComment::EVENT_PUBLISHED);
    }

    /**
     * @throws Internal
     */
    private function screenCommentReject(Motion $motion, int $commentId): void
    {
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId !== $motion->id) {
            throw new Internal(\Yii::t('comment', 'err_not_found'));
        }
        if (!$this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, null)) {
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
     * @throws FormError
     * @throws Internal
     */
    private function motionLikeDislike(Motion $motion, string $role, string $string, string $name, string $orga = '', string $gender = '', bool $nonPublic = false): void
    {
        $currentUser = User::getCurrentUser();
        if (!$motion->motionType->getMotionSupportPolicy()->checkCurrUser()) {
            throw new FormError('Supporting this motion is not possible');
        }

        MotionSupporter::createSupport($motion, $currentUser, $name, $orga, $role, $gender, $nonPublic);

        $this->getHttpSession()->setFlash('success', $string);
    }

    /**
     * @throws FormError
     * @throws Internal
     */
    private function motionLike(Motion $motion): void
    {
        if (!($motion->getLikeDislikeSettings() & SupportBase::LIKEDISLIKE_LIKE)) {
            throw new FormError('Not supported');
        }

        $name = (User::getCurrentUser() ? '' : $this->getHttpRequest()->post('likeName', ''));

        $this->motionLikeDislike($motion, MotionSupporter::ROLE_LIKE, \Yii::t('motion', 'like_done'), $name);
        ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_LIKE, $motion->id);
    }

    /**
     * @throws FormError
     * @throws Internal
     */
    private function motionSupport(Motion $motion): void
    {
        if (!$motion->isSupportingPossibleAtThisStatus()) {
            throw new FormError('Not possible given the current motion status');
        }
        foreach ($motion->getSupporters(true) as $supporter) {
            if (User::getCurrentUser() && $supporter->userId === User::getCurrentUser()->id) {
                $this->getHttpSession()->setFlash('success', \Yii::t('motion', 'support_already'));
                return;
            }
        }
        $supportType = $motion->getMyMotionType()->getMotionSupportTypeClass();
        $role = MotionSupporter::ROLE_SUPPORTER;
        $user = User::getCurrentUser();
        $gender = $this->getHttpRequest()->post('motionSupportGender', '');
        $nonPublic = ($supportType->getSettingsObj()->offerNonPublicSupports && $this->getHttpRequest()->post('motionSupportPublic') === null);
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

        if ($supportType->getSettingsObj()->hasOrganizations && $orga === '') {
            $this->getHttpSession()->setFlash('error', 'No organization entered');
            return;
        }
        if (trim($name) === '') {
            $this->getHttpSession()->setFlash('error', 'You need to enter a name');
            return;
        }
        $validGenderKeys = array_keys(SupportBase::getGenderSelection());
        if ($supportType->getSettingsObj()->contactGender === InitiatorForm::CONTACT_REQUIRED) {
            if (!in_array($gender, $validGenderKeys)) {
                $this->getHttpSession()->setFlash('error', 'You need to fill the gender field');
                return;
            }
        }
        if (!in_array($gender, $validGenderKeys)) {
            $gender = '';
        }

        $this->motionLikeDislike($motion, $role, \Yii::t('motion', 'support_done'), $name, $orga, $gender, $nonPublic);
        ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_SUPPORT, $motion->id);
    }

    /**
     * @throws FormError
     * @throws Internal
     */
    private function motionDislike(Motion $motion): void
    {
        if (!($motion->getLikeDislikeSettings() & SupportBase::LIKEDISLIKE_DISLIKE)) {
            throw new FormError('Not supported');
        }

        $name = (User::getCurrentUser() ? '' : $this->getHttpRequest()->post('likeName', ''));

        $this->motionLikeDislike($motion, MotionSupporter::ROLE_DISLIKE, \Yii::t('motion', 'dislike_done'), $name);
        ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_DISLIKE, $motion->id);
    }

    /**
     * @throws FormError
     */
    private function motionSupportRevoke(Motion $motion): void
    {
        $currentUser          = User::getCurrentUser();
        $loginlessSupported = MotionSupporter::getMyLoginlessSupportIds();
        foreach ($motion->motionSupporters as $supp) {
            if (($currentUser && $supp->userId === $currentUser->id) || in_array($supp->id, $loginlessSupported)) {
                if ($supp->role === MotionSupporter::ROLE_SUPPORTER) {
                    if (!$motion->isSupportingPossibleAtThisStatus()) {
                        throw new FormError('Not possible given the current motion status');
                    }
                }
                $motion->unlink('motionSupporters', $supp, true);
            }
        }

        ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_UNLIKE, $motion->id);
        $this->getHttpSession()->setFlash('success', \Yii::t('motion', 'neutral_done'));
    }

    private function motionSupportFinish(Motion $motion): void
    {
        if (!$motion->canFinishSupportCollection()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'support_finish_err'));
            return;
        }

        $motion->trigger(Motion::EVENT_SUBMITTED, new MotionEvent($motion));

        if ($motion->status == Motion::STATUS_SUBMITTED_SCREENED) {
            $motion->trigger(Motion::EVENT_PUBLISHED, new MotionEvent($motion));
        }

        ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_SUPPORT_FINISH, $motion->id);
        $this->getHttpSession()->setFlash('success', \Yii::t('motion', 'support_finish_done'));
    }

    /**
     * @throws Internal
     */
    private function motionAddTag(Motion $motion): void
    {
        if (!$this->consultation->havePrivilege(Privileges::PRIVILEGE_MOTION_STATUS_EDIT, PrivilegeQueryContext::motion($motion))) {
            throw new Internal(\Yii::t('comment', 'err_no_screening'));
        }
        foreach ($motion->getMyConsultation()->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
            if ($tag->id === intval($this->getHttpRequest()->post('tagId'))) {
                $motion->link('tags', $tag);
            }
        }
    }

    /**
     * @throws Internal
     */
    private function motionDelTag(Motion $motion): void
    {
        if (!$this->consultation->havePrivilege(Privileges::PRIVILEGE_MOTION_STATUS_EDIT, PrivilegeQueryContext::motion($motion))) {
            throw new Internal(\Yii::t('comment', 'err_no_screening'));
        }
        foreach ($motion->getMyConsultation()->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
            if ($tag->id === intval($this->getHttpRequest()->post('tagId'))) {
                $motion->unlink('tags', $tag, true);
            }
        }
    }

    private function setProposalAgree(Motion $motion): void
    {
        $procedureToken = $this->getHttpRequest()->get('procedureToken');
        if (!$motion->canSeeProposedProcedure($procedureToken) || !$motion->proposalFeedbackHasBeenRequested()) {
            $this->getHttpSession()->setFlash('error', 'Not allowed to perform this action');
            return;
        }

        $motion->proposalUserStatus = Motion::STATUS_ACCEPTED;
        $motion->save();
        $this->getHttpSession()->setFlash('success', \Yii::t('amend', 'proposal_user_saved'));
    }

    /**
     * @throws \Throwable
     */
    private function savePrivateNote(Motion $motion): void
    {
        $user      = User::getCurrentUser();
        $noteText  = trim($this->getHttpRequest()->post('noteText', ''));
        $paragraph = IntVal($this->getHttpRequest()->post('paragraphNo', -1));
        if (!$user) {
            return;
        }

        if ($this->getHttpRequest()->post('sectionId', 0) > 0) {
            $section = IntVal($this->getHttpRequest()->post('sectionId', 0));
        } else {
            $section = null;
        }

        $comment = $motion->getPrivateComment($section, $paragraph);
        if ($comment && $noteText === '') {
            $comment->delete();
            $motion->refresh();
            return;
        }

        if (!$comment) {
            $comment = new MotionComment();
        }
        $comment->motionId     = $motion->id;
        $comment->userId       = $user->id;
        $comment->text         = $noteText;
        $comment->status       = IComment::STATUS_PRIVATE;
        $comment->paragraph    = $paragraph;
        $comment->sectionId    = $section;
        $comment->dateCreation = date('Y-m-d H:i:s');
        $comment->name         = ($user->name ? $user->name : '-');
        $comment->save();

        $motion->refresh();

        throw new ResponseException(new RedirectResponse(UrlHelper::createMotionCommentUrl($comment)));
    }

    /**
     * @throws \Throwable
     */
    private function performShowActions(Motion $motion, int $commentId, array &$viewParameters): void
    {
        $post = $this->getHttpRequest()->post();
        if ($commentId === 0 && isset($post['commentId'])) {
            $commentId = intval($post['commentId']);
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
        } elseif (isset($post['addTag'])) {
            $this->motionAddTag($motion);
        } elseif (isset($post['delTag'])) {
            $this->motionDelTag($motion);
        } elseif (isset($post['writeComment'])) {
            $this->writeComment($motion, $viewParameters);
        } elseif (isset($post['setProposalAgree'])) {
            $this->setProposalAgree($motion);
        } elseif (isset($post['savePrivateNote'])) {
            $this->savePrivateNote($motion);
        }
    }

    public function actionDelProposalComment(string $motionSlug): ResponseInterface
    {
        $motion = $this->getMotionWithCheck($motionSlug);
        if (!$motion) {
            return new RestApiExceptionResponse(404, 'Motion not found');
        }

        $commentId = $this->getHttpRequest()->post('id');
        $comment   = MotionAdminComment::findOne(['id' => $commentId, 'motionId' => $motion->id]);
        if ($comment && User::isCurrentUser($comment->getMyUser())) {
            $comment->delete();
            return new JsonResponse(['success' => true]);
        } else {
            return new RestApiExceptionResponse(403, 'No permission to delete this comment');
        }
    }

    public function actionSaveProposalStatus(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new RestApiExceptionResponse(404, 'Motion not found');
        }
        if (!$motion->canEditLimitedProposedProcedure()) {
            return new RestApiExceptionResponse(403, 'Not permitted to change the status');
        }
        $canChangeProposalUnlimitedly = $motion->canEditProposedProcedure();

        $response = [];
        $msgAlert = null;
        $ppChanges = new ProposedProcedureChange(null);

        if ($this->getHttpRequest()->post('setStatus', null) !== null) {
            $originalMotionId = $motion->id;
            $originalProposalStatus = $motion->proposalStatus;
            foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                /** @var Motion $motion */
                $motion = $plugin::onBeforeProposedProcedureStatusSave($motion);
            }

            if ($canChangeProposalUnlimitedly) {
                $setStatus = intval($this->getHttpRequest()->post('setStatus'));
                if ($motion->proposalStatus !== $setStatus) {
                    $ppChanges->setProposalStatusChanges($motion->proposalStatus, $setStatus);
                    if ($motion->proposalUserStatus !== null) {
                        $msgAlert = \Yii::t('amend', 'proposal_user_change_reset');
                    }
                    $motion->proposalUserStatus = null;
                }
                $motion->proposalStatus = $setStatus;

                $ppChanges->setProposalCommentChanges($motion->proposalComment, $this->getHttpRequest()->post('proposalComment', ''));
                $motion->proposalComment = $this->getHttpRequest()->post('proposalComment', '');
            }

            $motion->setProposedProcedureTags($this->getHttpRequest()->post('tags', []), $ppChanges);

            if ($canChangeProposalUnlimitedly) {
                $proposalExplanationPre = $motion->proposalExplanation;
                if ($this->getHttpRequest()->post('proposalExplanation', null) !== null) {
                    if (trim($this->getHttpRequest()->post('proposalExplanation', '')) === '') {
                        $motion->proposalExplanation = null;
                    } else {
                        $motion->proposalExplanation = $this->getHttpRequest()->post('proposalExplanation', '');
                    }
                } else {
                    $motion->proposalExplanation = null;
                }
                $ppChanges->setProposalExplanationChanges($proposalExplanationPre, $motion->proposalExplanation);

                if ($this->getHttpRequest()->post('visible', 0)) {
                    if ($motion->proposalVisibleFrom === null) {
                        // Reload the page, to update section titles and permissions to edit the proposed procedure
                        $response['redirectToUrl'] = UrlHelper::createMotionUrl($motion, 'view');
                    }
                    $motion->setProposalPublished();
                } else {
                    $motion->proposalVisibleFrom = null;
                }
            }

            try {
                $motion->setProposalVotingPropertiesFromRequest(
                    $this->getHttpRequest()->post('votingStatus', null),
                    $this->getHttpRequest()->post('votingBlockId', null),
                    $this->getHttpRequest()->post('votingItemBlockId', []),
                    $this->getHttpRequest()->post('votingItemBlockName', ''),
                    $this->getHttpRequest()->post('votingBlockTitle', ''),
                    true,
                    $ppChanges
                );
            } catch (FormError $e) {
                return new RestApiExceptionResponse(400, $e->getMessage());
            }

            if ($ppChanges->hasChanges()) {
                ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_SET_PROPOSAL, $motion->id, $ppChanges->jsonSerialize());
            }

            $response['success'] = false;
            if ($motion->save()) {
                $response['success'] = true;
            }

            $this->consultation->refresh();
            $response['html']        = $this->renderPartial('_set_proposed_procedure', [
                'motion'   => $motion,
                'msgAlert' => $msgAlert,
                'context'   => $this->getHttpRequest()->post('context', 'view'),
            ]);
            $response['proposalStr'] = $motion->getFormattedProposalStatus(true);

            if ($motion->proposalStatus === IMotion::STATUS_MODIFIED_ACCEPTED && $originalProposalStatus !== $motion->proposalStatus) {
                $response['redirectToUrl'] = UrlHelper::createMotionUrl($motion, 'edit-proposed-change');
            } elseif ($motion->id !== $originalMotionId) {
                // This can happen if a plugin enforces the creation of a new motion when saving
                $response['redirectToUrl'] = UrlHelper::createMotionUrl($motion, 'view');
            }
        }

        if ($this->getHttpRequest()->post('notifyProposer') || $this->getHttpRequest()->post('sendAgain')) {
            try {
                new MotionProposedProcedure(
                    $motion,
                    $this->getHttpRequest()->post('text'),
                    $this->getHttpRequest()->post('fromName'),
                    $this->getHttpRequest()->post('replyTo')
                );
                $motion->proposalNotification = date('Y-m-d H:i:s');
                $motion->save();
                $response['success'] = true;
                $response['html']    = $this->renderPartial('_set_proposed_procedure', [
                    'motion'   => $motion,
                    'msgAlert' => $msgAlert,
                    'context'  => $this->getHttpRequest()->post('context', 'view'),
                ]);
            } catch (MailNotSent $e) {
                $response['success'] = false;
                $response['error']   = 'The mail could not be sent: ' . $e->getMessage();
            }
        }

        if ($this->getHttpRequest()->post('setProposerHasAccepted')) {
            $motion->proposalUserStatus = Motion::STATUS_ACCEPTED;
            $motion->save();
            ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_ACCEPT_PROPOSAL, $motion->id);
            $response['success'] = true;
            $response['html']    = $this->renderPartial('_set_proposed_procedure', [
                'motion'   => $motion,
                'msgAlert' => $msgAlert,
            ]);
        }

        if ($this->getHttpRequest()->post('writeComment')) {
            $adminComment               = new MotionAdminComment();
            $adminComment->userId       = User::getCurrentUser()->id;
            $adminComment->text         = $this->getHttpRequest()->post('writeComment');
            $adminComment->status       = MotionAdminComment::TYPE_PROPOSED_PROCEDURE;
            $adminComment->dateCreation = date('Y-m-d H:i:s');
            $adminComment->motionId     = $motion->id;
            if (!$adminComment->save()) {
                return new RestApiExceptionResponse(500, 'could not save the comment');
            }

            $response['success'] = true;
            $response['comment'] = [
                'username'      => $adminComment->getMyUser()->name,
                'id'            => $adminComment->id,
                'text'          => $adminComment->text,
                'delLink'       => UrlHelper::createMotionUrl($motion, 'del-proposal-comment'),
                'dateFormatted' => Tools::formatMysqlDateTime($adminComment->dateCreation),
            ];
        }

        return new JsonResponse($response);
    }

    public function actionEditProposedChange(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404, 'Motion not found');
        }
        if (!$motion->canEditProposedProcedure()) {
            return new HtmlErrorResponse(403, 'Not permitted to edit the proposed procedure');
        }

        if ($this->getHttpRequest()->post('reset', null) !== null) {
            $reference = $motion->getMyProposalReference();
            if ($reference && $reference->status === Amendment::STATUS_PROPOSED_MODIFIED_MOTION) {
                foreach ($reference->sections as $section) {
                    $section->delete();
                }

                $motion->proposalReferenceId = null;
                $motion->save();

                $reference->delete();
            }
            $motion->flushCacheItems(['procedure']);
        }

        $form = new ProposedChangeForm($motion);

        $msgSuccess = null;
        $msgAlert   = null;

        if ($this->getHttpRequest()->post('save', null) !== null) {
            $form->save($this->getHttpRequest()->post(), $_FILES);
            $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));

            if ($motion->proposalUserStatus !== null) {
                $this->getHttpSession()->setFlash('info', \Yii::t('amend', 'proposal_user_change_reset'));
            }
            $motion->proposalUserStatus = null;
            $motion->save();
            $motion->flushCacheItems(['procedure']);

            return new RedirectResponse(UrlHelper::createMotionUrl($motion, 'view'));
        }

        return new HtmlResponse($this->render('edit_proposed_change', [
            'msgSuccess' => $msgSuccess,
            'msgAlert'   => $msgAlert,
            'motion'     => $motion,
            'form'       => $form,
        ]));
    }

    public function actionSaveEditorial(string $motionSlug, int $sectionId): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new RestApiExceptionResponse(404, 'Motion not found');
        }
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CHANGE_EDITORIAL, PrivilegeQueryContext::motion($motion))) {
            return new RestApiExceptionResponse(403, 'Not permitted to change the editorial');
        }

        $section = null;
        foreach ($motion->getActiveSections(ISectionType::TYPE_TEXT_EDITORIAL) as $searchSection) {
            if ($searchSection->sectionId === $sectionId) {
                $section = $searchSection;
            }
        }
        if (!$section) {
            return new RestApiExceptionResponse(404, 'Section not found');
        }
        /** @var TextEditorial $sectionType */
        $sectionType = $section->getSectionType();
        $sectionType->setEditorialData(
            $this->getPostValue('data'),
            $this->getPostValue('author'),
            $this->getPostValue('updateDate') ? new \DateTime() : $sectionType->getSectionMetadata()['lastUpdate']
        );
        $section->save();

        return new JsonResponse([
            'success' => true,
            'html' => $section->getData(),
            'metadataFormatted' => $sectionType->getFormattedSectionMetadata(true),
        ]);
    }
}
