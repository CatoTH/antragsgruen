<?php

namespace app\controllers;

use app\models\consultationLog\ProposedProcedureChange;
use app\models\settings\{AntragsgruenApp, PrivilegeQueryContext, Privileges};
use app\views\pdfLayouts\IPDFLayout;
use app\models\http\{BinaryFileResponse,
    HtmlErrorResponse,
    HtmlResponse,
    JsonResponse,
    RedirectResponse,
    ResponseInterface,
    RestApiExceptionResponse,
    RestApiResponse};
use app\components\{HTMLTools, IMotionStatusFilter, Tools, UrlHelper};
use app\models\db\{Amendment, AmendmentAdminComment, AmendmentSupporter, ConsultationLog, IMotion, ISupporter, Motion, User};
use app\models\events\AmendmentEvent;
use app\models\exceptions\{FormError, MailNotSent, ResponseException};
use app\models\forms\{AdminMotionFilterForm, AmendmentEditForm, ProposedChangeForm};
use app\models\notifications\AmendmentProposedProcedure;
use app\models\sectionTypes\ISectionType;
use app\views\amendment\LayoutHelper;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;

class AmendmentController extends Base
{
    use AmendmentActionsTrait;
    use AmendmentMergingTrait;

    public function actionPdf(string $motionSlug, int $amendmentId): ResponseInterface
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        $this->amendment = $amendment;
        if (!$amendment) {
            return new HtmlErrorResponse(404, 'Amendment not found');
        }

        $selectedPdfLayout = IPDFLayout::getPdfLayoutForMotionType($amendment->getMyMotionType());

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        if (!($hasLaTeX && $selectedPdfLayout->latexId !== null) && $selectedPdfLayout->id === null) {
            return new HtmlErrorResponse(404, \Yii::t('motion', 'err_no_pdf'));
        }

        if (!$amendment->isReadable()) {
            return new HtmlErrorResponse(404, \Yii::t('amend', 'err_not_visible'));
        }

        if ($selectedPdfLayout->isHtmlToPdfLayout()) {
            $pdf = LayoutHelper::createPdfFromHtml($amendment);
        } elseif ($selectedPdfLayout->latexId !== null) {
            $pdf = LayoutHelper::createPdfLatex($amendment);
        } else {
            $pdf = LayoutHelper::createPdfTcpdf($amendment);
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_PDF,
            $pdf,
            false,
            $amendment->getFilenameBase(false),
            $this->layoutParams->isRobotsIndex($this->action)
        );
    }

    public function actionPdfcollection(int $inactive = 0): ResponseInterface
    {
        $search = AdminMotionFilterForm::getForConsultationFromRequest($this->consultation, $this->consultation->motions, $this->getRequestValue('Search'));
        $amendments = $search->getAmendmentsForExport($this->consultation, ($inactive === 1));

        if (count($amendments) === 0) {
            return new HtmlErrorResponse(404, \Yii::t('amend', 'none_yet'));
        }
        $texTemplate = null;
        $toShowAmendments = [];
        foreach ($amendments as $amendmentGroups) {
            if ($amendmentGroups['motion']->getMyMotionType()->amendmentsOnly) {
                continue;
            }
            // If we have multiple motion types, we just take the template from the first one.
            if ($texTemplate === null) {
                $texTemplate = $amendmentGroups['motion']->getMyMotionType()->texTemplate;
            }
            $toShowAmendments = array_merge($toShowAmendments, $amendmentGroups['amendments']);
        }
        if (count($toShowAmendments) === 0) {
            return new HtmlErrorResponse(404, \Yii::t('amend', 'none_yet'));
        }

        $selectedPdfLayout = IPDFLayout::getPdfLayoutForMotionType($amendments[0]['motion']->getMyMotionType());

        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        if (!($hasLaTeX && $selectedPdfLayout->latexId !== null) && $selectedPdfLayout->id === null) {
            return new HtmlErrorResponse(404, \Yii::t('motion', 'err_no_pdf'));
        }

        if ($selectedPdfLayout->isHtmlToPdfLayout()) {
            $pdf = $this->renderPartial('pdf_collection_html2pdf', ['amendments' => $toShowAmendments]);
        } elseif ($selectedPdfLayout->latexId !== null) {
            $pdf = $this->renderPartial('pdf_collection_tex', [
                'amendments'  => $toShowAmendments,
                'texTemplate' => $texTemplate,
            ]);
        } else {
            $pdf = $this->renderPartial('pdf_collection_tcpdf', ['amendments' => $toShowAmendments]);
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_PDF,
            $pdf,
            false,
            null,
            $this->layoutParams->isRobotsIndex($this->action)
        );
    }

    public function actionOdt(string $motionSlug, int $amendmentId): ResponseInterface
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        $this->amendment = $amendment;
        if (!$amendment) {
            return new HtmlErrorResponse(404, 'Amendment not found');
        }

        if (!$amendment->isReadable()) {
            return new HtmlErrorResponse(404, \Yii::t('amend', 'err_not_visible'));
        }

        $doc = $amendment->getMyMotionType()->createOdtTextHandler();
        LayoutHelper::printAmendmentToOdt($amendment, $doc);

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_ODT,
            $doc->finishAndGetDocument(),
            false,
            $amendment->getFilenameBase(false),
            $this->layoutParams->isRobotsIndex($this->action)
        );
    }

    public function actionRest(string $motionSlug, int $amendmentId): RestApiResponse
    {
        $this->handleRestHeaders(['GET']);

        try {
            $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId, null, true);
            $this->amendment = $amendment;
        } catch (\Exception $e) {
            return new RestApiExceptionResponse(404, $e->getMessage());
        }

        if (!$amendment->isReadable()) {
            return new RestApiExceptionResponse(403, 'Amendment is not readable');
        }

        return new RestApiResponse(200, null, $this->renderPartial('rest_get', ['amendment' => $amendment]));
    }

    public function actionView(string $motionSlug, int $amendmentId, int $commentId = 0, ?string $procedureToken = null): ResponseInterface
    {
        $this->layout = 'column2';

        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId, 'view');
        $this->amendment = $amendment;
        if (!$amendment) {
            return new HtmlErrorResponse(404, 'Amendment not found');
        }

        if ($this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, null)) {
            $adminEdit = UrlHelper::createUrl(['admin/amendment/update', 'amendmentId' => $amendmentId]);
        } else {
            $adminEdit = null;
        }

        if (!$amendment->isReadable()) {
            return new HtmlErrorResponse(404, \Yii::t('amend', 'err_not_visible'));
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
            $this->performShowActions($amendment, $commentId, $amendmentViewParams);
        } catch (ResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->getHttpSession()->setFlash('error', $e->getMessage());
        }

        $amendmentViewParams['supportStatus'] = AmendmentSupporter::getCurrUserSupportStatus($amendment);

        return new HtmlResponse($this->render('view', $amendmentViewParams));
    }

    public function actionAjaxDiff(string $motionSlug, int $amendmentId): ResponseInterface
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        $this->amendment = $amendment;
        if (!$amendment) {
            return new HtmlErrorResponse(404, 'Amendment not found');
        }

        return new HtmlResponse($this->renderPartial('ajax_diff', ['amendment' => $amendment]));
    }

    public function actionCreatedone(string $motionSlug, int $amendmentId, string $fromMode): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        /** @var Amendment|null $amendment */
        $amendment = Amendment::findOne(
            [
                'id'       => $amendmentId,
                'motionId' => $motion->id
            ]
        );
        if (!$amendment) {
            $this->getHttpSession()->setFlash('error', \Yii::t('amend', 'err_not_found'));
            return new RedirectResponse(UrlHelper::homeUrl());
        }

        return new HtmlResponse($this->render('create_done', ['amendment' => $amendment, 'mode' => $fromMode]));
    }

    public function actionCreateconfirm(string $motionSlug, int $amendmentId, string $fromMode): ResponseInterface
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
            return new RedirectResponse(UrlHelper::homeUrl());
        }
        if (!$amendment->canEditText()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_edit_permission'));

            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        if ($this->isPostSet('modify')) {
            $nextUrl = ['amendment/edit', 'amendmentId' => $amendment->id, 'motionSlug' => $motionSlug];
            return new RedirectResponse(UrlHelper::createUrl($nextUrl));
        }

        if ($this->isPostSet('confirm')) {
            $amendment->trigger(Amendment::EVENT_SUBMITTED, new AmendmentEvent($amendment));

            if ($amendment->status === Amendment::STATUS_SUBMITTED_SCREENED) {
                $amendment->trigger(Amendment::EVENT_PUBLISHED, new AmendmentEvent($amendment));
            }

            return new RedirectResponse(UrlHelper::createAmendmentUrl($amendment, 'createdone', ['fromMode' => $fromMode]));
        } else {
            return new HtmlResponse($this->render('create_confirm', [
                'amendment'     => $amendment,
                'mode'          => $fromMode,
                'deleteDraftId' => $this->getHttpRequest()->get('draftId'),
            ]));
        }
    }

    public function actionEdit(string $motionSlug, int $amendmentId): ResponseInterface
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
            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        if (!$amendment->canEditText()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('amend', 'err_edit_forbidden'));
            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        $fromMode = ($amendment->status === Amendment::STATUS_DRAFT ? 'create' : 'edit');
        $form = new AmendmentEditForm($amendment->getMyMotion(), $amendment->getMyAgendaItem(), $amendment, null, null);
        if (!$amendment->canEditInitiators()) {
            $form->setAllowEditingInitiators(false);
        }

        if ($this->isPostSet('save')) {
            $amendment->flushCacheWithChildren(null);
            $form->setAttributes($this->getHttpRequest()->post(), $_FILES);
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
                    return new RedirectResponse(UrlHelper::createUrl($nextUrl));
                } else {
                    return new HtmlResponse($this->render('edit_done', ['amendment' => $amendment]));
                }
            } catch (\Throwable $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }
        }

        return new HtmlResponse($this->render(
            'edit_form',
            [
                'mode'         => $fromMode,
                'form'         => $form,
                'consultation' => $this->consultation,
            ]
        ));
    }

    /**
     * @throws \app\models\exceptions\NotAmendable
     */
    public function actionCreate(string $motionSlug, int $agendaItemId = 0, int $cloneFrom = 0, int $createFromAmendment = 0, ?int $sectionId = null, ?int $paragraphNo = null): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_not_found'));
            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        if (!$motion->isCurrentlyAmendable()) {
            if ($motion->isCurrentlyAmendable(true, true)) {
                $loginUrl = UrlHelper::createLoginUrl(['amendment/create', 'motionSlug' => $motion->getMotionSlug()]);
                return new RedirectResponse($loginUrl);
            } else {
                return new HtmlErrorResponse(403, \Yii::t('amend', 'err_create_permission'));
            }
        }

        if ($agendaItemId > 0) {
            $agendaItem = $this->consultation->getAgendaItem($agendaItemId);
        } else {
            $agendaItem = null;
        }

        $form = new AmendmentEditForm($motion, $agendaItem, null, $sectionId, $paragraphNo);
        $supportType = $motion->getMyMotionType()->getAmendmentSupportTypeClass();
        $iAmAdmin = $this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, null);

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
                return new RedirectResponse(UrlHelper::createUrl($nextUrl));
            } catch (\Throwable $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }
        } elseif ($cloneFrom > 0) {
            $adoptAmend = $this->consultation->getAmendment($cloneFrom);
            $form->cloneSupporters($adoptAmend);
            $form->cloneAmendmentText($adoptAmend, true);
        } elseif ($createFromAmendment > 0 && $motion->getMyMotionType()->getSettingsObj()->allowAmendmentsToAmendments) {
            $adoptAmend = $this->consultation->getAmendment($createFromAmendment);
            if ($adoptAmend->motionId === $motion->id) {
                $form->cloneAmendmentText($adoptAmend, false);
                $form->toAnotherAmendment = $adoptAmend->id;
            }
        }

        if (count($form->supporters) == 0) {
            $form->supporters[] = AmendmentSupporter::createInitiator($this->consultation, $supportType, $iAmAdmin);
        }

        return new HtmlResponse($this->render(
            'edit_form',
            [
                'mode'         => 'create',
                'consultation' => $this->consultation,
                'form'         => $form,
            ]
        ));
    }

    public function actionWithdraw(int $amendmentId): ResponseInterface
    {
        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment) {
            $this->getHttpSession()->setFlash('error', \Yii::t('amend', 'err_not_found'));
            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        if (!$amendment->canWithdraw()) {
            $this->getHttpSession()->setFlash('error', \Yii::t('amend', 'err_withdraw_forbidden'));
            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        if ($this->isPostSet('cancel')) {
            return new RedirectResponse(UrlHelper::createAmendmentUrl($amendment));
        }

        if ($this->isPostSet('withdraw')) {
            $amendment->withdraw();
            $this->getHttpSession()->setFlash('success', \Yii::t('amend', 'widthdraw_done'));
            return new RedirectResponse(UrlHelper::createAmendmentUrl($amendment));
        }

        return new HtmlResponse($this->render('withdraw', ['amendment' => $amendment]));
    }

    public function actionSaveProposalStatus(string $motionSlug, int $amendmentId): ResponseInterface
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        $this->amendment = $amendment;
        if (!$amendment) {
            return new RestApiExceptionResponse(404, 'Amendment not found');
        }
        if (!$amendment->canEditLimitedProposedProcedure()) {
            return new RestApiExceptionResponse(403, 'Not permitted to change the status');
        }
        $canChangeProposalUnlimitedly = $amendment->canEditProposedProcedure();

        $response = [];
        $msgAlert = null;
        $ppChanges = new ProposedProcedureChange(null);

        if ($this->getHttpRequest()->post('setStatus', null) !== null) {
            $originalProposalStatus = $amendment->proposalStatus;

            foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                /** @var Amendment $amendment */
                $amendment = $plugin::onBeforeProposedProcedureStatusSave($amendment);
            }

            if ($canChangeProposalUnlimitedly) {
                $setStatus = intval($this->getHttpRequest()->post('setStatus'));
                if ($amendment->proposalStatus !== $setStatus) {
                    $ppChanges->setProposalStatusChanges($amendment->proposalStatus, $setStatus);
                    if ($amendment->proposalUserStatus !== null) {
                        $msgAlert = \Yii::t('amend', 'proposal_user_change_reset');
                    }
                    $amendment->proposalUserStatus = null;
                }
                $amendment->proposalStatus = $setStatus;

                $ppChanges->setProposalCommentChanges($amendment->proposalComment, $this->getHttpRequest()->post('proposalComment', ''));
                $amendment->proposalComment = $this->getHttpRequest()->post('proposalComment', '');
            }

            $amendment->setProposedProcedureTags($this->getHttpRequest()->post('tags', []), $ppChanges);

            if ($canChangeProposalUnlimitedly) {
                $proposalExplanationPre = $amendment->proposalExplanation;
                if ($this->getHttpRequest()->post('proposalExplanation', null) !== null) {
                    if (trim($this->getHttpRequest()->post('proposalExplanation', '')) === '') {
                        $amendment->proposalExplanation = null;
                    } else {
                        $amendment->proposalExplanation = $this->getHttpRequest()->post('proposalExplanation', '');
                    }
                } else {
                    $amendment->proposalExplanation = null;
                }
                $ppChanges->setProposalExplanationChanges($proposalExplanationPre, $amendment->proposalExplanation);

                if ($this->getHttpRequest()->post('visible', 0)) {
                    if ($amendment->proposalVisibleFrom === null) {
                        // Reload the page, to update section titles and permissions to edit the proposed procedure
                        $response['redirectToUrl'] = UrlHelper::createAmendmentUrl($amendment, 'view');
                    }
                    $amendment->setProposalPublished();
                } else {
                    $amendment->proposalVisibleFrom = null;
                }
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

                if ($amendment->proposalStatus === IMotion::STATUS_MODIFIED_ACCEPTED && $originalProposalStatus !== $amendment->proposalStatus) {
                    $response['redirectToUrl'] = UrlHelper::createAmendmentUrl($amendment, 'edit-proposed-change');
                }
            } catch (FormError $e) {
                $response['success'] = false;
                $response['error'] = $e->getMessage();
                return new JsonResponse($response);
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
            $adminComment->status       = AmendmentAdminComment::TYPE_PROPOSED_PROCEDURE;
            $adminComment->dateCreation = date('Y-m-d H:i:s');
            $adminComment->amendmentId  = $amendment->id;
            if (!$adminComment->save()) {
                $this->getHttpResponse()->statusCode = 500;
                $response['success']             = false;
                return new JsonResponse($response);
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

        return new JsonResponse($response);
    }

    public function actionEditProposedChange(string $motionSlug, int $amendmentId): ResponseInterface
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        $this->amendment = $amendment;
        if (!$amendment) {
            return new HtmlErrorResponse(404, 'Amendment not found');
        }
        if (!$amendment->canEditProposedProcedure()) {
            return new HtmlErrorResponse(403, 'Not permitted to change the proposed procedure');
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

        $form = new ProposedChangeForm($amendment);

        $msgSuccess = null;
        $msgAlert   = null;

        if ($this->getHttpRequest()->post('save', null) !== null) {
            $form->save($this->getHttpRequest()->post(), $_FILES);
            $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));

            if ($amendment->proposalUserStatus !== null) {
                $this->getHttpSession()->setFlash('info', \Yii::t('amend', 'proposal_user_change_reset'));
            }
            $amendment->proposalUserStatus = null;
            $amendment->save();
            $amendment->flushCacheItems(['procedure']);

            return new RedirectResponse(UrlHelper::createAmendmentUrl($amendment, 'view'));
        }

        return new HtmlResponse($this->render('edit_proposed_change', [
            'msgSuccess' => $msgSuccess,
            'msgAlert'   => $msgAlert,
            'amendment'  => $amendment,
            'form'       => $form,
        ]));
    }

    public function actionEditProposedChangeCheck(string $motionSlug, int $amendmentId): ResponseInterface
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);
        $this->amendment = $amendment;
        if (!$amendment) {
            return new RestApiExceptionResponse(404, 'Amendment not found');
        }
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CHANGE_PROPOSALS, PrivilegeQueryContext::amendment($amendment))) {
            return new RestApiExceptionResponse(403, 'Not permitted to change the status');
        }

        $checkAgainstAmendments = $amendment->getMyMotion()->getAmendmentsProposedToBeIncluded(true, [$amendment->id]);
        if (count($checkAgainstAmendments) > 100) {
            return new JsonResponse([
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

        return new JsonResponse([
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
    public function actionGotoPrefix(string $prefix1, string $prefix2): ResponseInterface
    {
        try {
            /** @var Amendment|null $amendment */
            $amendment = Amendment::find()->joinWith('motionJoin')->where([
                'motion.consultationId' => $this->consultation->id,
                'motion.titlePrefix'    => $prefix1,
                'amendment.titlePrefix' => $prefix2,
            ])->one();

            if ($amendment && $amendment->isReadable()) {
                return new RedirectResponse($amendment->getLink());
            }
        } catch (\Exception $e) {}

        return new HtmlErrorResponse(404, 'Amendment not found');
    }
}
