<?php

namespace app\controllers\admin;

use app\models\consultationLog\ProposedProcedureChange;
use app\models\forms\AdminMotionFilterForm;
use app\views\amendment\LayoutHelper as AmendmentLayoutHelper;
use app\views\pdfLayouts\IPDFLayout;
use app\models\http\{BinaryFileResponse, HtmlErrorResponse, HtmlResponse, RedirectResponse, ResponseInterface};
use app\models\settings\{AntragsgruenApp, PrivilegeQueryContext, Privileges};
use app\components\{IMotionStatusFilter, Tools, UrlHelper, ZipWriter};
use app\models\db\{Amendment, AmendmentSupporter, ConsultationLog, ConsultationSettingsTag, Motion, repostory\MotionRepository, User};
use app\models\events\AmendmentEvent;
use app\models\exceptions\FormError;
use app\models\forms\AmendmentEditForm;
use app\views\amendment\LayoutHelper;

class AmendmentController extends AdminBase
{
    public const REQUIRED_PRIVILEGES = [
        Privileges::PRIVILEGE_MOTION_STATUS_EDIT,
        Privileges::PRIVILEGE_MOTION_TEXT_EDIT,
        Privileges::PRIVILEGE_MOTION_INITIATORS,
    ];

    public function actionOdslist(bool $textCombined = false, int $inactive = 0): BinaryFileResponse
    {
        $search = AdminMotionFilterForm::getForConsultationFromRequest($this->consultation, $this->consultation->motions, $this->getRequestValue('Search'));
        $amendments = $search->getAmendmentsForExport($this->consultation, ($inactive === 1));

        $ods = $this->renderPartial('ods_list', [
            'amendments'   => $amendments,
            'textCombined' => $textCombined,
        ]);

        return new BinaryFileResponse(BinaryFileResponse::TYPE_ODS, $ods, true,'amendments');
    }

    public function actionXlsxList(bool $textCombined = false, int $inactive = 0): BinaryFileResponse
    {
        $search = AdminMotionFilterForm::getForConsultationFromRequest($this->consultation, $this->consultation->motions, $this->getRequestValue('Search'));
        $amendments = $search->getAmendmentsForExport($this->consultation, ($inactive === 1));

        $ods = $this->renderPartial('xlsx_list', [
            'amendments'   => $amendments,
            'textCombined' => $textCombined,
        ]);

        return new BinaryFileResponse(BinaryFileResponse::TYPE_XLSX, $ods, true,'amendments');
    }

    public function actionOdslistShort(int $textCombined = 0, int $inactive = 0, int $maxLen = 2000): BinaryFileResponse
    {
        $search = AdminMotionFilterForm::getForConsultationFromRequest($this->consultation, $this->consultation->motions, $this->getRequestValue('Search'));
        $amendments = $search->getAmendmentsForExport($this->consultation, ($inactive === 1));

        $ods = $this->renderPartial('ods_list_short', [
            'amendments'   => $amendments,
            'textCombined' => ($textCombined === 1),
            'maxLen'       => $maxLen,
        ]);
        return new BinaryFileResponse(BinaryFileResponse::TYPE_ODS, $ods, true, 'amendments');
    }

    public function actionPdflist(int $inactive = 0): HtmlResponse
    {
        $search = AdminMotionFilterForm::getForConsultationFromRequest($this->consultation, $this->consultation->motions, $this->getRequestValue('Search'));
        $amendments = $search->getAmendmentsForExport($this->consultation, ($inactive === 1));

        return new HtmlResponse(
            $this->render('pdf_list', ['consultation' => $this->consultation, 'amendments' => $amendments])
        );
    }

    public function actionPdfziplist(int $inactive = 0): BinaryFileResponse
    {
        $search = AdminMotionFilterForm::getForConsultationFromRequest($this->consultation, $this->consultation->motions, $this->getRequestValue('Search'));
        $amendments = $search->getAmendmentsForExport($this->consultation, ($inactive === 1));

        $zip = new ZipWriter();
        foreach ($amendments as $amendmentGroup) {
            if ($amendmentGroup['motion']->getMyMotionType()->amendmentsOnly || !$amendmentGroup['motion']->getMyMotionType()->hasPdfLayout()) {
                continue;
            }
            foreach ($amendmentGroup['amendments'] as $amendment) {
                $selectedPdfLayout = IPDFLayout::getPdfLayoutForMotionType($amendment->getMyMotionType());
                if ($selectedPdfLayout->isHtmlToPdfLayout()) {
                    $file = AmendmentLayoutHelper::createPdfFromHtml($amendment);
                } elseif ($selectedPdfLayout->latexId !== null) {
                    $file = AmendmentLayoutHelper::createPdfLatex($amendment);
                } else {
                    $file = AmendmentLayoutHelper::createPdfTcpdf($amendment);
                }
                $zip->addFile($amendment->getFilenameBase(false) . '.pdf', $file);
            }
        }

        return new BinaryFileResponse(BinaryFileResponse::TYPE_ZIP, $zip->getContentAndFlush(), true, 'amendments_pdf');
    }

    public function actionOdtziplist(int $inactive = 0): BinaryFileResponse
    {
        $search = AdminMotionFilterForm::getForConsultationFromRequest($this->consultation, $this->consultation->motions, $this->getRequestValue('Search'));
        $amendments = $search->getAmendmentsForExport($this->consultation, ($inactive === 1));

        $zip       = new ZipWriter();
        foreach ($amendments as $amendmentGroup) {
            if ($amendmentGroup['motion']->getMyMotionType()->amendmentsOnly) {
                continue;
            }
            foreach ($amendmentGroup['amendments'] as $amendment) {
                $doc = $amendment->getMyMotionType()->createOdtTextHandler();
                LayoutHelper::printAmendmentToOdt($amendment, $doc);
                $zip->addFile($amendment->getFilenameBase(false) . '.odt', $doc->finishAndGetDocument());
            }
        }

        return new BinaryFileResponse(BinaryFileResponse::TYPE_ZIP, $zip->getContentAndFlush(), true, 'amendments_odt');
    }

    /**
     * @throws \Exception
     */
    private function saveAmendmentSupporters(Amendment $amendment): void
    {
        $names         = $this->getHttpRequest()->post('supporterName', []);
        $orgas         = $this->getHttpRequest()->post('supporterOrga', []);
        $genders       = $this->getHttpRequest()->post('supporterGender', []);
        $preIds        = $this->getHttpRequest()->post('supporterId', []);
        $newSupporters = [];
        /** @var AmendmentSupporter[] $preSupporters */
        $preSupporters = [];
        foreach ($amendment->getSupporters(true) as $supporter) {
            $preSupporters[$supporter->id] = $supporter;
        }
        for ($i = 0; $i < count($names); $i++) {
            if (trim($names[$i]) === '' && trim($orgas[$i]) === '') {
                continue;
            }
            if (isset($preSupporters[$preIds[$i]])) {
                $supporter = $preSupporters[$preIds[$i]];
            } else {
                $supporter               = new AmendmentSupporter();
                $supporter->amendmentId  = $amendment->id;
                $supporter->role         = AmendmentSupporter::ROLE_SUPPORTER;
                $supporter->personType   = AmendmentSupporter::PERSON_NATURAL;
                $supporter->dateCreation = date('Y-m-d H:i:s');
            }
            $supporter->name         = $names[$i];
            $supporter->organization = $orgas[$i];
            $supporter->position     = $i;
            $supporter->setExtraDataEntry('gender', $genders[$i] ?? null);
            if (!$supporter->save()) {
                var_dump($supporter->getErrors());
                die();
            }
            $newSupporters[$supporter->id] = $supporter;
        }

        foreach ($preSupporters as $supporter) {
            if (!isset($newSupporters[$supporter->id])) {
                $supporter->delete();
            }
        }

        $amendment->refresh();
    }

    private function saveAmendmentInitiator(Amendment $motion): void
    {
        if ($this->getHttpRequest()->post('initiatorSet') !== '1') {
            return;
        }
        $setType = $this->getHttpRequest()->post('initiatorSetType');
        $setUsername = $this->getHttpRequest()->post('initiatorSetUsername');
        $user = User::findByAuthTypeAndName($setType, $setUsername);

        if ($setUsername && !$user) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_user_not_found'));
            return;
        }

        foreach ($motion->getInitiators() as $initiator) {
            $initiator->userId = ($user ? $user->id : null);
            $initiator->save();
            $initiator->refresh();
        }
        $motion->refresh();
    }

    public function actionUpdate(int $amendmentId): ResponseInterface
    {
        $consultation = $this->consultation;

        $amendment = $consultation->getAmendment($amendmentId);
        if (!$amendment) {
            return new RedirectResponse(UrlHelper::createUrl('admin/motion-list/index'));
        }
        $this->checkConsistency($amendment->getMyMotion(), $amendment);

        $privCtx = PrivilegeQueryContext::amendment($amendment);
        if (!User::haveOneOfPrivileges($consultation, self::REQUIRED_PRIVILEGES, $privCtx)) {
            return new HtmlErrorResponse(403, \Yii::t('admin', 'no_access'));
        }

        $this->layout = 'column2';

        $post = $this->getHttpRequest()->post();

        if ($this->isPostSet('screen') && $amendment->isInScreeningProcess() && User::havePrivilege($consultation, Privileges::PRIVILEGE_SCREENING, $privCtx)) {
            $toSetPrefix = (mb_strlen($post['titlePrefix']) > 45 ? mb_substr($post['titlePrefix'], 0, 45) : $post['titlePrefix']);
            if ($amendment->getMyMotion()->findAmendmentWithPrefix($toSetPrefix, $amendment)) {
                $this->getHttpSession()->setFlash('error', \Yii::t('admin', 'amend_prefix_collision'));
            } else {
                $amendment->status = Amendment::STATUS_SUBMITTED_SCREENED;
                $amendment->titlePrefix = $toSetPrefix;
                $amendment->save();
                $amendment->trigger(Amendment::EVENT_PUBLISHED, new AmendmentEvent($amendment));
                $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'amend_screened'));
            }
        }

        if ($this->isPostSet('delete') && User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_DELETE, $privCtx)) {
            $amendment->status = Amendment::STATUS_DELETED;
            $amendment->save();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'amend_deleted'));
            return new RedirectResponse(UrlHelper::createUrl('admin/motion-list/index'));
        }

        if ($this->isPostSet('save') &&  User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_STATUS_EDIT, $privCtx)) {
            if (!isset($post['edittext'])) {
                unset($post['sections']);
            }
            $form = new AmendmentEditForm($amendment->getMyMotion(), $amendment->getMyAgendaItem(), $amendment, null, null);
            $form->setAdminMode(true);
            $form->setAttributes($post, $_FILES);

            $votingData = $amendment->getVotingData();
            $votingData->setFromPostData($post['votes']);
            $amendment->setVotingData($votingData);

            try {
                $form->saveAmendment($amendment);
            } catch (FormError $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }

            $amdat                        = $post['amendment'];
            $amendment->dateCreation      = Tools::dateBootstraptime2sql($amdat['dateCreation']);
            $amendment->noteInternal      = $amdat['noteInternal'];
            $amendment->globalAlternative = (isset($amdat['globalAlternative']) ? 1 : 0);
            $amendment->dateResolution    = null;
            $amendment->notCommentable = (isset($amdat['notCommentable']) ? 1 : 0);

            $amendment->status       = intval($amdat['status']);
            if ($amendment->status === Motion::STATUS_OBSOLETED_BY_MOTION) {
                $amendment->statusString = (string)intval($amdat['statusStringMotion']);
            } elseif ($amendment->status === Motion::STATUS_OBSOLETED_BY_AMENDMENT) {
                $amendment->statusString = (string)intval($amdat['statusStringAmendment']);
            } else {
                $amendment->statusString = mb_substr($amdat['statusString'], 0, 55);
            }

            $amendment->setExtraDataKey(
                Amendment::EXTRA_DATA_VIEW_MODE_FULL,
                (isset($amdat['viewMode']) && $amdat['viewMode'] === '1')
            );
            if ($amdat['dateResolution'] !== '') {
                $amendment->dateResolution = Tools::dateBootstraptime2sql($amdat['dateResolution']);
            }
            $amendment->agendaItemId = null;
            if (isset($amdat['agendaItemId'])) {
                foreach ($consultation->agendaItems as $agendaItem) {
                    if ($agendaItem->id === intval($amdat['agendaItemId'])) {
                        $amendment->agendaItemId = intval($amdat['agendaItemId']);
                    }
                }
            }

            $toSetPrefix = (mb_strlen($amdat['titlePrefix']) > 45 ? mb_substr($amdat['titlePrefix'], 0, 45) : $amdat['titlePrefix']);
            if ($amendment->getMyMotion()->findAmendmentWithPrefix($toSetPrefix, $amendment)) {
                $this->getHttpSession()->setFlash('error', \Yii::t('admin', 'amend_prefix_collision'));
            } else {
                $amendment->titlePrefix = $toSetPrefix;
            }

            foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                $plugin::setAmendmentExtraSettingsFromForm($amendment, $post);
            }

            $ppChanges = new ProposedProcedureChange(null);
            try {
                $amendment->setProposalVotingPropertiesFromRequest(
                    $this->getHttpRequest()->post('votingStatus', null),
                    $this->getHttpRequest()->post('votingBlockId', null),
                    $this->getHttpRequest()->post('votingItemBlockId', []),
                    $this->getHttpRequest()->post('votingItemBlockName', ''),
                    $this->getHttpRequest()->post('newBlockTitle', ''),
                    false,
                    $ppChanges
                );
            } catch (FormError $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }
            if ($ppChanges->hasChanges()) {
                ConsultationLog::logCurrUser($amendment->getMyConsultation(), ConsultationLog::AMENDMENT_SET_PROPOSAL, $amendment->id, $ppChanges->jsonSerialize());
            }

            $amendment->save();

            if (User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, $privCtx)) {
                $this->saveAmendmentSupporters($amendment);
                $this->saveAmendmentInitiator($amendment);
            }

            // This forces recalculating the motion's view page. This is necessary at least when the text has changed
            // or the names of the initiators.
            $amendment->getMyMotion()->flushViewCache();

            $amendment->flushCache(true);
            $amendment->refresh();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'saved'));
        }

        $form = new AmendmentEditForm($amendment->getMyMotion(),$amendment->getMyAgendaItem(), $amendment, null, null);
        $form->setAdminMode(true);

        return new HtmlResponse($this->render('update', ['amendment' => $amendment, 'form' => $form]));
    }

    public function actionOpenslides(): BinaryFileResponse
    {
        $amendments = [];
        $filter = IMotionStatusFilter::onlyUserVisible($this->consultation, false);
        foreach ($filter->getFilteredConsultationIMotionsSorted() as $motion) {
            if (!is_a($motion, Motion::class)) {
                continue;
            }
            foreach ($motion->getFilteredAndSortedAmendments($filter) as $amendment) {
                $amendments[] = $amendment;
            }
        }

        $csv = $this->renderPartial('openslides_list', [
            'amendments' => $amendments,
        ]);
        return new BinaryFileResponse(BinaryFileResponse::TYPE_CSV, $csv, true, 'Amendments');
    }
}
