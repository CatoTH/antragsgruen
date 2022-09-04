<?php

namespace app\controllers;

use app\models\consultationLog\ProposedProcedureChange;
use app\components\{HTMLTools, Tools, UrlHelper};
use app\models\db\{Amendment,
    AmendmentAdminComment,
    AmendmentSupporter,
    ConsultationLog,
    ConsultationSettingsTag,
    ConsultationUserGroup,
    ISupporter,
    Motion,
    User};
use app\models\events\AmendmentEvent;
use app\models\exceptions\{FormError, MailNotSent, NotFound};
use app\models\forms\{AmendmentEditForm, AmendmentProposedChangeForm};
use app\models\notifications\AmendmentProposedProcedure;
use app\models\sectionTypes\ISectionType;
use app\views\amendment\LayoutHelper;
use yii\helpers\Html;
use yii\web\{NotFoundHttpException, Response};

class AmendmentController extends Base
{
    use AmendmentActionsTrait;
    use AmendmentMergingTrait;

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionPdf(string $motionSlug, int $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        $this->amendment = $amendment;
        if (!$amendment) {
            return '';
        }

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        if (!($hasLaTeX && $amendment->getMyMotionType()->texTemplateId) && !$amendment->getMyMotionType()->getPDFLayoutClass()) {
            $this->showErrorpage(404, \Yii::t('motion', 'err_no_pdf'));
            return '';
        }

        if (!$amendment->isReadable()) {
            return $this->render('view_not_visible', ['amendment' => $amendment, 'adminEdit' => false]);
        }

        $filename = $amendment->getFilenameBase(false) . '.pdf';
        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/pdf');
        $this->getHttpResponse()->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            $this->getHttpResponse()->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($hasLaTeX && $amendment->getMyMotionType()->texTemplateId) {
            return LayoutHelper::createPdfLatex($amendment);
        } else {
            return LayoutHelper::createPdfTcpdf($amendment);
        }
    }

    /**
     * @return string
     */
    public function actionPdfcollection(int $withdrawn = 0)
    {
        $withdrawn = ($withdrawn === 1);
        $motions   = $this->consultation->getVisibleIMotionsSorted($withdrawn);
        if (count($motions) === 0) {
            $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
        }
        $amendments  = [];
        $texTemplate = null;
        foreach ($motions as $motion) {
            if (!is_a($motion, Motion::class) || $motion->getMyMotionType()->amendmentsOnly) {
                continue;
            }
            // If we have multiple motion types, we just take the template from the first one.
            if ($texTemplate === null) {
                $texTemplate = $motion->getMyMotionType()->texTemplate;
            }
            $amendments = array_merge($amendments, $motion->getVisibleAmendmentsSorted($withdrawn));
        }
        if (count($amendments) == 0) {
            $this->showErrorpage(404, \Yii::t('amend', 'none_yet'));
        }

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/pdf');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            $this->getHttpResponse()->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        if ($hasLaTeX && $texTemplate) {
            return $this->renderPartial('pdf_collection_tex', [
                'amendments'  => $amendments,
                'texTemplate' => $texTemplate,
            ]);
        } else {
            return $this->renderPartial('pdf_collection_tcpdf', ['amendments' => $amendments]);
        }
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionOdt(string $motionSlug, int $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        $this->amendment = $amendment;
        if (!$amendment) {
            return '';
        }

        if (!$amendment->isReadable()) {
            return $this->render('view_not_visible', ['amendment' => $amendment, 'adminEdit' => false]);
        }

        $filename = $amendment->getFilenameBase(false) . '.odt';
        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
        $this->getHttpResponse()->headers->add('Content-disposition', 'filename="' . addslashes($filename) . '"');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            $this->getHttpResponse()->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $this->renderPartial('view_odt', ['amendment' => $amendment]);
    }

    /**
     * @return string
     */
    public function actionRest(string $motionSlug, int $amendmentId)
    {
        $this->handleRestHeaders(['GET']);

        try {
            $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId, null, true);
            $this->amendment = $amendment;
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        if (!$amendment->isReadable()) {
            return $this->returnRestResponseFromException(new NotFound('Amendment is not readable'));
        }

        return $this->returnRestResponse(200, $this->renderPartial('rest_get', ['amendment' => $amendment]));
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionView(string $motionSlug, int $amendmentId, int $commentId = 0, ?string $procedureToken = null)
    {
        $this->layout = 'column2';

        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId, 'view');
        $this->amendment = $amendment;
        if (!$amendment) {
            return '';
        }

        if ($this->consultation->havePrivilege(ConsultationUserGroup::PRIVILEGE_SCREENING)) {
            $adminEdit = UrlHelper::createUrl(['admin/amendment/update', 'amendmentId' => $amendmentId]);
        } else {
            $adminEdit = null;
        }

        if (!$amendment->isReadable()) {
            return $this->render('view_not_visible', ['amendment' => $amendment, 'adminEdit' => $adminEdit]);
        }

        $openedComments      = [];
        $amendmentViewParams = [
            'amendment'      => $amendment,
            'openedComments' => $openedComments,
            'adminEdit'      => $adminEdit,
            'commentForm'    => null,
            'procedureToken' => $procedureToken,
        ];

        try {
            $this->performShowActions($amendment, intval($commentId), $amendmentViewParams);
        } catch (\Throwable $e) {
            $this->getHttpSession()->setFlash('error', $e->getMessage());
        }

        $supportStatus = '';
        if (!\Yii::$app->user->isGuest) {
            foreach ($amendment->amendmentSupporters as $supp) {
                if ($supp->userId == User::getCurrentUser()->id) {
                    $supportStatus = $supp->role;
                }
            }
        }
        $amendmentViewParams['supportStatus'] = $supportStatus;


        return $this->render('view', $amendmentViewParams);
    }

    /**
     * @return string
     */
    public function actionAjaxDiff(string $motionSlug, int $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        $this->amendment = $amendment;
        if (!$amendment) {
            return '';
        }

        return $this->renderPartial('ajax_diff', ['amendment' => $amendment]);
    }

    /**
     * @return string
     */
    public function actionCreatedone(string $motionSlug, int $amendmentId, string $fromMode)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne(
            [
                'id'       => $amendmentId,
                'motionId' => $motion->id
            ]
        );
        return $this->render('create_done', ['amendment' => $amendment, 'mode' => $fromMode]);
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionCreateconfirm(string $motionSlug, int $amendmentId, string $fromMode)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        /** @var Amendment|null $amendment */
        $amendment = Amendment::findOne(
            [
                'id'       => $amendmentId,
                'motionId' => $motion->id,
                'status'   => Amendment::STATUS_DRAFT
            ]
        );
        if (!$amendment) {
            $this->getHttpSession()->setFlash('error', \Yii::t('amend', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }
        if (!$amendment->canEdit()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_edit_permission'));

            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if ($this->isPostSet('modify')) {
            $nextUrl = ['amendment/edit', 'amendmentId' => $amendment->id, 'motionSlug' => $motionSlug];
            return $this->redirect(UrlHelper::createUrl($nextUrl));
        }

        if ($this->isPostSet('confirm')) {
            $amendment->trigger(Amendment::EVENT_SUBMITTED, new AmendmentEvent($amendment));

            if ($amendment->status === Amendment::STATUS_SUBMITTED_SCREENED) {
                $amendment->trigger(Amendment::EVENT_PUBLISHED, new AmendmentEvent($amendment));
            }

            return $this->redirect(UrlHelper::createAmendmentUrl($amendment, 'createdone', ['fromMode' => $fromMode]));
        } else {
            return $this->render('create_confirm', [
                'amendment'     => $amendment,
                'mode'          => $fromMode,
                'deleteDraftId' => $this->getHttpRequest()->get('draftId'),
            ]);
        }
    }

    /**
     * @return string
     */
    public function actionEdit(string $motionSlug, int $amendmentId)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        /** @var Amendment|null $amendment */
        $amendment = Amendment::findOne(
            [
                'id'       => $amendmentId,
                'motionId' => $motion->id,
            ]
        );
        if (!$amendment) {
            $this->getHttpSession()->setFlash('error', \Yii::t('amend', 'err_not_found'));
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (!$amendment->canEdit()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('amend', 'err_edit_forbidden'));
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $fromMode = ($amendment->status == Amendment::STATUS_DRAFT ? 'create' : 'edit');
        $form     = new AmendmentEditForm($amendment->getMyMotion(), $amendment->getMyAgendaItem(), $amendment);

        if ($this->isPostSet('save')) {
            $amendment->flushCacheWithChildren(null);
            $form->setAttributes([$this->getHttpRequest()->post(), $_FILES]);
            try {
                $form->saveAmendment($amendment);

                if ($amendment->isVisible()) {
                    ConsultationLog::logCurrUser($this->consultation, ConsultationLog::AMENDMENT_CHANGE, $amendment->id);
                }

                if ($amendment->status == Amendment::STATUS_DRAFT) {
                    $nextUrl = [
                        'amendment/createconfirm',
                        'motionSlug'  => $motionSlug,
                        'amendmentId' => $amendment->id,
                        'fromMode'    => $fromMode,
                        'draftId'     => $this->getRequestValue('draftId'),
                    ];
                    return $this->redirect(UrlHelper::createUrl($nextUrl));
                } else {
                    return $this->render('edit_done', ['amendment' => $amendment]);
                }
            } catch (\Throwable $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }
        }

        return $this->render(
            'edit_form',
            [
                'mode'         => $fromMode,
                'form'         => $form,
                'consultation' => $this->consultation,
            ]
        );
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     * @throws \app\models\exceptions\NotAmendable
     */
    public function actionCreate(string $motionSlug, int $agendaItemId = 0, int $cloneFrom = 0, int $createFromAmendment = 0)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (!$motion->isCurrentlyAmendable()) {
            if ($motion->isCurrentlyAmendable(true, true)) {
                $loginUrl = UrlHelper::createLoginUrl(['amendment/create', 'motionSlug' => $motion->getMotionSlug()]);
                return $this->redirect($loginUrl);
            } else {
                $this->showErrorpage(403, \Yii::t('amend', 'err_create_permission'));
                return '';
            }
        }

        if ($agendaItemId > 0) {
            $agendaItem = $this->consultation->getAgendaItem(intval($agendaItemId));
        } else {
            $agendaItem = null;
        }

        $form = new AmendmentEditForm($motion, $agendaItem, null);
        $supportType = $motion->getMyMotionType()->getAmendmentSupportTypeClass();
        $iAmAdmin = $this->consultation->havePrivilege(ConsultationUserGroup::PRIVILEGE_SCREENING);

        if ($this->isPostSet('save')) {
            try {
                $amendment = $form->createAmendment();

                // Supporting members are not collected in the form, but need to be copied a well
                if ($supportType->collectSupportersBeforePublication() && $cloneFrom && $iAmAdmin) {
                    $adoptAmend = $this->consultation->getAmendment($cloneFrom);
                    foreach ($adoptAmend->getSupporters(true) as $supp) {
                        $suppNew = new AmendmentSupporter();
                        $suppNew->setAttributes($supp->getAttributes());
                        $suppNew->id           = null;
                        $suppNew->amendmentId  = $amendment->id;
                        $suppNew->extraData    = $supp->extraData;
                        $suppNew->dateCreation = date('Y-m-d H:i:s');
                        if ($supp->isNonPublic()) {
                            $suppNew->setExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_NON_PUBLIC, true);
                        }
                        $suppNew->save();
                    }
                }

                $nextUrl = [
                    'amendment/createconfirm',
                    'motionSlug'  => $motionSlug,
                    'amendmentId' => $amendment->id,
                    'fromMode'    => 'create',
                    'draftId'     => $this->getRequestValue('draftId'),
                ];
                return $this->redirect(UrlHelper::createUrl($nextUrl));
            } catch (\Throwable $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }
        } elseif ($cloneFrom > 0) {
            $adoptAmend = $this->consultation->getAmendment($cloneFrom);
            $form->cloneSupporters($adoptAmend);
            $form->cloneAmendmentText($adoptAmend);
        } elseif ($createFromAmendment > 0 && $motion->getMyMotionType()->getSettingsObj()->allowAmendmentsToAmendments) {
            $adoptAmend = $this->consultation->getAmendment($createFromAmendment);
            if ($adoptAmend->motionId === $motion->id) {
                $form->cloneAmendmentText($adoptAmend);
                $form->toAnotherAmendment = $adoptAmend->id;
            }
        }

        if (count($form->supporters) == 0) {
            $form->supporters[] = AmendmentSupporter::createInitiator($supportType, $iAmAdmin);
        }

        return $this->render(
            'edit_form',
            [
                'mode'         => 'create',
                'consultation' => $this->consultation,
                'form'         => $form,
            ]
        );
    }

    /**
     * @return string
     */
    public function actionWithdraw(int $amendmentId)
    {
        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment) {
            $this->getHttpSession()->setFlash('error', \Yii::t('amend', 'err_not_found'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (!$amendment->canWithdraw()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('amend', 'err_withdraw_forbidden'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if ($this->isPostSet('cancel')) {
            return $this->redirect(UrlHelper::createAmendmentUrl($amendment));
        }

        if ($this->isPostSet('withdraw')) {
            $amendment->withdraw();
            $this->getHttpSession()->setFlash('success', \Yii::t('amend', 'widthdraw_done'));
            return $this->redirect(UrlHelper::createAmendmentUrl($amendment));
        }

        return $this->render('withdraw', ['amendment' => $amendment]);
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionSaveProposalStatus(string $motionSlug, int $amendmentId)
    {
        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/json');

        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        $this->amendment = $amendment;
        if (!$amendment) {
            $this->getHttpResponse()->statusCode = 404;
            return 'Amendment not found';
        }
        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CHANGE_PROPOSALS)) {
            $this->getHttpResponse()->statusCode = 403;
            return 'Not permitted to change the status';
        }

        $response = [];
        $msgAlert = null;
        $ppChanges = new ProposedProcedureChange(null);

        if ($this->getHttpRequest()->post('setStatus', null) !== null) {
            $setStatus = intval($this->getHttpRequest()->post('setStatus'));
            if ($amendment->proposalStatus !== $setStatus) {
                $ppChanges->setProposalStatusChanges($amendment->proposalStatus, $setStatus);
                if ($amendment->proposalUserStatus !== null) {
                    $msgAlert = \Yii::t('amend', 'proposal_user_change_reset');
                }
                $amendment->proposalUserStatus = null;
            }
            $amendment->proposalStatus  = $setStatus;

            $ppChanges->setProposalCommentChanges($amendment->proposalComment, $this->getHttpRequest()->post('proposalComment', ''));
            $amendment->proposalComment = $this->getHttpRequest()->post('proposalComment', '');

            $oldTags = $amendment->getProposedProcedureTags();
            $newTags = [];
            $changed = false;
            foreach ($this->getHttpRequest()->post('tags', []) as $newTag) {
                $tag = $amendment->getMyConsultation()->getExistingTagOrCreate(ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE, $newTag, 0);
                if (!isset($oldTags[$tag->getNormalizedName()])) {
                    $amendment->link('tags', $tag);
                    $changed = true;
                }
                $newTags[] = ConsultationSettingsTag::normalizeName($newTag);
            }
            foreach ($oldTags as $tagKey => $tag) {
                if (!in_array($tagKey, $newTags)) {
                    $amendment->unlink('tags', $tag, true);
                    $changed = true;
                }
            }
            if ($changed) {
                $ppChanges->setProposalTagsHaveChanged(array_keys($oldTags), $newTags);
            }

            $proposalExplanationPre = $amendment->proposalExplanation;
            if ($this->getHttpRequest()->post('proposalExplanation', null) !== null) {
                if (trim($this->getHttpRequest()->post('proposalExplanation', '') === '')) {
                    $amendment->proposalExplanation = null;
                } else {
                    $amendment->proposalExplanation = $this->getHttpRequest()->post('proposalExplanation', '');
                }
            } else {
                $amendment->proposalExplanation = null;
            }
            $ppChanges->setProposalExplanationChanges($proposalExplanationPre, $amendment->proposalExplanation);

            if ($this->getHttpRequest()->post('visible', 0)) {
                $amendment->setProposalPublished();
            } else {
                $amendment->proposalVisibleFrom = null;
            }

            try {
                $amendment->setProposalVotingPropertiesFromRequest(
                    $this->getHttpRequest()->post('votingStatus', null),
                    $this->getHttpRequest()->post('votingBlockId', null),
                    $this->getHttpRequest()->post('votingItemBlockId', []),
                    $this->getHttpRequest()->post('votingItemBlockName', ''),
                    $this->getHttpRequest()->post('votingBlockTitle', ''),
                    true,
                    $ppChanges
                );
            } catch (FormError $e) {
                $response['success'] = false;
                $response['error']   = $e->getMessage();
                return json_encode($response);
            }

            if ($ppChanges->hasChanges()) {
                ConsultationLog::logCurrUser($amendment->getMyConsultation(), ConsultationLog::AMENDMENT_SET_PROPOSAL, $amendment->id, $ppChanges->jsonSerialize());
            }

            $response['success'] = false;
            if ($amendment->save()) {
                $response['success'] = true;
            }
            $amendment->flushCacheItems(['procedure']);

            $this->consultation->refresh();
            $response['html']        = $this->renderPartial('_set_proposed_procedure', [
                'amendment' => $amendment,
                'msgAlert'  => $msgAlert,
                'context'   => $this->getHttpRequest()->post('context', 'view'),
            ]);
            $response['proposalStr'] = $amendment->getFormattedProposalStatus(true);
        }

        if ($this->getHttpRequest()->post('notifyProposer') || $this->getHttpRequest()->post('sendAgain')) {
            try {
                new AmendmentProposedProcedure(
                    $amendment,
                    $this->getHttpRequest()->post('text'),
                    $this->getHttpRequest()->post('fromName'),
                    $this->getHttpRequest()->post('replyTo')
                );
                $amendment->proposalNotification = date('Y-m-d H:i:s');
                $amendment->save();
                $amendment->flushCacheItems(['procedure']);
                $response['success'] = true;
                $response['html']    = $this->renderPartial('_set_proposed_procedure', [
                    'amendment' => $amendment,
                    'msgAlert'  => $msgAlert,
                    'context'   => $this->getHttpRequest()->post('context', 'view'),
                ]);
            } catch (MailNotSent $e) {
                $response['success'] = false;
                $response['error']   = 'The mail could not be sent: ' . $e->getMessage();
            }
        }

        if ($this->getHttpRequest()->post('setProposerHasAccepted')) {
            $amendment->proposalUserStatus = Amendment::STATUS_ACCEPTED;
            $amendment->save();
            $amendment->flushCacheItems(['procedure']);
            ConsultationLog::logCurrUser($amendment->getMyConsultation(), ConsultationLog::AMENDMENT_ACCEPT_PROPOSAL, $amendment->id);
            $response['success'] = true;
            $response['html']        = $this->renderPartial('_set_proposed_procedure', [
                'amendment' => $amendment,
                'msgAlert'  => $msgAlert,
                'context'   => $this->getHttpRequest()->post('context', 'view'),
            ]);
        }

        if ($this->getHttpRequest()->post('writeComment')) {
            $adminComment               = new AmendmentAdminComment();
            $adminComment->userId       = User::getCurrentUser()->id;
            $adminComment->text         = $this->getHttpRequest()->post('writeComment');
            $adminComment->status       = AmendmentAdminComment::PROPOSED_PROCEDURE;
            $adminComment->dateCreation = date('Y-m-d H:i:s');
            $adminComment->amendmentId  = $amendment->id;
            if (!$adminComment->save()) {
                $this->getHttpResponse()->statusCode = 500;
                $response['success']             = false;
                return json_encode($response);
            }
            $amendment->flushCacheItems(['procedure']);

            $response['success'] = true;
            $response['comment'] = [
                'username'      => $adminComment->getMyUser()->name,
                'id'            => $adminComment->id,
                'text'          => $adminComment->text,
                'delLink'       => UrlHelper::createAmendmentUrl($amendment, 'del-proposal-comment'),
                'dateFormatted' => Tools::formatMysqlDateTime($adminComment->dateCreation),
            ];
        }

        return json_encode($response);
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionEditProposedChange(string $motionSlug, int $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        $this->amendment = $amendment;
        if (!$amendment) {
            $this->getHttpResponse()->statusCode = 404;
            return 'Amendment not found';
        }
        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CHANGE_PROPOSALS)) {
            $this->getHttpResponse()->statusCode = 403;
            return 'Not permitted to change the status';
        }


        if ($this->getHttpRequest()->post('reset', null) !== null) {
            $reference = $amendment->getMyProposalReference();
            if ($reference && $reference->status === Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT) {
                foreach ($reference->sections as $section) {
                    $section->delete();
                }

                $amendment->proposalReferenceId = null;
                $amendment->save();

                $reference->delete();
            }
            $amendment->flushCacheItems(['procedure']);
        }

        $form = new AmendmentProposedChangeForm($amendment);

        $msgSuccess = null;
        $msgAlert   = null;

        if ($this->getHttpRequest()->post('save', null) !== null) {
            $form->save($this->getHttpRequest()->post(), $_FILES);
            $msgSuccess = \Yii::t('base', 'saved');

            if ($amendment->proposalUserStatus !== null) {
                $msgAlert = \Yii::t('amend', 'proposal_user_change_reset');
            }
            $amendment->proposalUserStatus = null;
            $amendment->save();
            $amendment->flushCacheItems(['procedure']);
        }

        return $this->render('edit_proposed_change', [
            'msgSuccess' => $msgSuccess,
            'msgAlert'   => $msgAlert,
            'amendment'  => $amendment,
            'form'       => $form,
        ]);
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionEditProposedChangeCheck(string $motionSlug, int $amendmentId)
    {
        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/json');

        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        $this->amendment = $amendment;
        if (!$amendment) {
            $this->getHttpResponse()->statusCode = 404;
            return json_encode([
                'error' => 'Amendment not found',
                'collisions' => [],
            ]);
        }
        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CHANGE_PROPOSALS)) {
            $this->getHttpResponse()->statusCode = 403;
            return json_encode([
                'error' => 'Not permitted to change the status',
                'collisions' => [],
            ]);
        }

        $checkAgainstAmendments = $amendment->getMyMotion()->getAmendmentsProposedToBeIncluded(true, [$amendment->id]);
        if (count($checkAgainstAmendments) > 100) {
            return json_encode([
                'error' => 'Too many amendments to check for collisions (max. 100)',
                'collisions' => [],
            ]);
        }

        $newSections = $this->getHttpRequest()->post('sections', []);
        foreach ($newSections as $sectionId => $section) {
            $newSections[$sectionId] = HTMLTools::cleanSimpleHtml($section);
        }

        /** @var Amendment[] $collidesWith */
        $collidesWith = [];
        foreach ($checkAgainstAmendments as $compAmend) {
            foreach ($compAmend->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $coll = $section->getRewriteCollisions($newSections[$section->sectionId], false);
                if (count($coll) > 0 && !in_array($compAmend, $collidesWith, true)) {
                    $collidesWith[] = $compAmend;
                }
            }
        }

        return json_encode([
            'error' => null,
            'collisions' => array_map(function (Amendment $amend) {
                // Keep in sync with edit_proposed_change.php
                $title = $amend->getShortTitle();
                if ($amend->proposalStatus == Amendment::STATUS_VOTE) {
                    $title .= ' (' . \Yii::t('amend', 'proposal_voting') . ')';
                }
                $html = '<li>' . Html::a($title, UrlHelper::createAmendmentUrl($amend), ['target' => '_blank']);
                $html .= HTMLTools::amendmentDiffTooltip($amend, 'top', 'fixedBottom');
                $html .= '</li>';

                return [
                    'id'    => $amend->id,
                    'title' => $amend->getShortTitle(),
                    'html'  => $html,
                ];
            }, $collidesWith),
        ]);
    }

    /**
     * URL: /[consultationPrefix]/[motionPrefix]/[amendmentPrefix]
     *
     * @throws NotFoundHttpException
     */
    public function actionGotoPrefix(string $prefix1, string $prefix2): Response
    {
        try {
            /** @var Amendment|null $amendment */
            $amendment = Amendment::find()->joinWith('motionJoin')->where([
                'motion.consultationId' => $this->consultation->id,
                'motion.titlePrefix'    => $prefix1,
                'amendment.titlePrefix' => $prefix2,
            ])->one();

            if ($amendment && $amendment->isReadable()) {
                return $this->getHttpResponse()->redirect($amendment->getLink());
            }
        } catch (\Exception $e) {
            throw new NotFoundHttpException();
        }
        throw new NotFoundHttpException();
    }
}
