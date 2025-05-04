<?php

namespace app\controllers\admin;

use app\models\exceptions\{Access, NotFound, ExceptionBase, ResponseException};
use app\views\pdfLayouts\IPDFLayout;
use app\components\{IMotionStatusFilter, MotionSorter, RequestContext, Tools, UrlHelper, ZipWriter};
use app\models\db\{Amendment, Consultation, ConsultationAgendaItem, IMotion, Motion, User};
use app\models\forms\AdminMotionFilterForm;
use app\models\http\{BinaryFileResponse, HtmlErrorResponse, HtmlResponse, RedirectResponse, ResponseInterface};
use app\models\settings\{AntragsgruenApp, PrivilegeQueryContext, Privileges};
use app\views\amendment\LayoutHelper as AmendmentLayoutHelper;
use app\views\motion\LayoutHelper as MotionLayoutHelper;

class MotionListController extends AdminBase
{
    public static function haveAccessToList(Consultation $consultation): bool
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }

        $ctx = PrivilegeQueryContext::anyRestriction();
        $privilegeSee       = $user->hasPrivilege($consultation, Privileges::PRIVILEGE_MOTION_SEE_UNPUBLISHED, $ctx);
        $privilegeScreening = $user->hasPrivilege($consultation, Privileges::PRIVILEGE_SCREENING, $ctx);
        $privilegeProposal  = $user->hasPrivilege($consultation, Privileges::PRIVILEGE_CHANGE_PROPOSALS, $ctx);
        $privilegeDeleting  = $user->hasPrivilege($consultation, Privileges::PRIVILEGE_MOTION_DELETE, $ctx);
        $privilegeStatus    = $user->hasPrivilege($consultation, Privileges::PRIVILEGE_MOTION_STATUS_EDIT, $ctx);
        $privilege = ($privilegeSee || $privilegeScreening || $privilegeProposal || $privilegeDeleting || $privilegeStatus);

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $override = $plugin::canSeeFullMotionList($consultation, $user);
            if ($override !== null) {
                $privilege = $override;
            }
        }

        return $privilege;
    }

    /**
     * @throws Access|NotFound
     */
    private function getMotionWithPrivilege(int $motionId, int $privilege): Motion
    {
        $motion = $this->consultation->getMotion((string)$motionId);
        if (!$motion) {
            throw new NotFound('Motion not found');
        }
        if (!User::getCurrentUser()->hasPrivilege($this->consultation, $privilege, PrivilegeQueryContext::motion($motion))) {
            throw new Access('No screening permissions');
        }
        return $motion;
    }

    /**
     * @throws Access|NotFound
     */
    private function getAmendmentWithPrivilege(int $amendmentId, int $privilege): Amendment
    {
        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment) {
            throw new NotFound('Amendment not found');
        }
        if (!User::getCurrentUser()->hasPrivilege($this->consultation, $privilege, PrivilegeQueryContext::amendment($amendment))) {
            throw new Access('No screening permissions');
        }
        return $amendment;
    }


    /**
     * @throws Access|NotFound
     */
    protected function actionListallScreeningMotions(): void
    {
        if ($this->isRequestSet('motionScreen')) {
            $motion = $this->getMotionWithPrivilege((int)$this->getRequestValue('motionScreen'), Privileges::PRIVILEGE_SCREENING);
            $motion->setScreened();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_screened'));
        }
        if ($this->isRequestSet('motionUnscreen')) {
            $motion = $this->getMotionWithPrivilege((int)$this->getRequestValue('motionUnscreen'), Privileges::PRIVILEGE_SCREENING);
            $motion->setUnscreened();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_unscreened'));
        }
        if ($this->isRequestSet('motionDelete')) {
            $motion = $this->getMotionWithPrivilege((int)$this->getRequestValue('motionDelete'), Privileges::PRIVILEGE_MOTION_DELETE);
            $motion->setDeleted();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_deleted'));
        }

        if (!$this->isRequestSet('motions') || !$this->isRequestSet('save')) {
            return;
        }
        if ($this->isRequestSet('screen')) {
            foreach ($this->getRequestValue('motions') as $motionId) {
                try {
                    $motion = $this->getMotionWithPrivilege((int)$motionId, Privileges::PRIVILEGE_SCREENING);
                    $motion->setScreened();
                } catch (ExceptionBase $e) {} // The user probably just accidentally selected an invalid motion, so let's just continue
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_screened_pl'));
        }

        if ($this->isRequestSet('unscreen')) {
            foreach ($this->getRequestValue('motions') as $motionId) {
                try {
                    $motion = $this->getMotionWithPrivilege((int)$motionId, Privileges::PRIVILEGE_SCREENING);
                    $motion->setUnscreened();
                } catch (ExceptionBase $e) {} // The user probably just accidentally selected an invalid motion, so let's just continue
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_unscreened_pl'));
        }

        if ($this->isRequestSet('delete')) {
            foreach ($this->getRequestValue('motions') as $motionId) {
                try {
                    $motion = $this->getMotionWithPrivilege((int)$motionId, Privileges::PRIVILEGE_MOTION_DELETE);
                    $motion->setDeleted();
                } catch (ExceptionBase $e) {} // The user probably just accidentally selected an invalid motion, so let's just continue
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_deleted_pl'));
        }
    }

    /**
     * @throws Access
     * @throws NotFound
     */
    protected function actionListallScreeningAmendments(): void
    {
        if ($this->isRequestSet('amendmentScreen')) {
            $amendment = $this->getAmendmentWithPrivilege((int)$this->getRequestValue('amendmentScreen'), Privileges::PRIVILEGE_SCREENING);
            $amendment->setScreened();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_am_screened'));
        }
        if ($this->isRequestSet('amendmentUnscreen')) {
            $amendment = $this->getAmendmentWithPrivilege((int)$this->getRequestValue('amendmentUnscreen'), Privileges::PRIVILEGE_SCREENING);
            $amendment->setUnscreened();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_am_unscreened'));
        }
        if ($this->isRequestSet('amendmentDelete')) {
            $amendment = $this->getAmendmentWithPrivilege((int)$this->getRequestValue('amendmentDelete'), Privileges::PRIVILEGE_MOTION_DELETE);
            $amendment->setDeleted();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_am_deleted'));
        }
        if (!$this->isRequestSet('amendments') || !$this->isRequestSet('save')) {
            return;
        }
        if ($this->isRequestSet('screen')) {
            foreach ($this->getRequestValue('amendments') as $amendmentId) {
                try {
                    $amendment = $this->getAmendmentWithPrivilege($amendmentId, Privileges::PRIVILEGE_SCREENING);
                    $amendment->setScreened();
                } catch (ExceptionBase $e) {} // The user probably just accidentally selected an invalid amendment, so let's just continue
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_am_screened_pl'));
        }

        if ($this->isRequestSet('unscreen')) {
            foreach ($this->getRequestValue('amendments') as $amendmentId) {
                try {
                    $amendment = $this->getAmendmentWithPrivilege($amendmentId, Privileges::PRIVILEGE_SCREENING);
                    $amendment->setUnscreened();
                } catch (ExceptionBase $e) {} // The user probably just accidentally selected an invalid amendment, so let's just continue
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_am_unscreened_pl'));
        }

        if ($this->isRequestSet('delete')) {
            foreach ($this->getRequestValue('amendments') as $amendmentId) {
                try {
                    $amendment = $this->getAmendmentWithPrivilege($amendmentId, Privileges::PRIVILEGE_MOTION_DELETE);
                    $amendment->setDeleted();
                } catch (ExceptionBase $e) {} // The user probably just accidentally selected an invalid amendment, so let's just continue
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_am_deleted_pl'));
        }
    }

    protected function actionListallProposalAmendments(): void
    {
        if ($this->isRequestSet('proposalVisible')) {
            foreach ($this->getRequestValue('amendments', []) as $amendmentId) {
                try {
                    $amendment = $this->getAmendmentWithPrivilege($amendmentId, Privileges::PRIVILEGE_CHANGE_PROPOSALS);
                    $amendment->setProposalPublished();
                } catch (ExceptionBase) {} // The user probably just accidentally selected an invalid amendment, so let's just continue
            }
            foreach ($this->getRequestValue('motions', []) as $motionId) {
                try {
                    $motionId = $this->getMotionWithPrivilege($motionId, Privileges::PRIVILEGE_CHANGE_PROPOSALS);
                    $motionId->setProposalPublished();
                } catch (ExceptionBase) {} // The user probably just accidentally selected an invalid motion, so let's just continue
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_proposal_published_pl'));
        }
    }

    /**
     * @param Motion[] $motions
     */
    private function getSearchForm(array $motions): AdminMotionFilterForm
    {
        return AdminMotionFilterForm::getForConsultationFromRequest(
            $this->consultation,
            $motions,
            $this->getRequestValue('Search')
        );
    }

    public function actionIndex(?string $motionId = null): ResponseInterface
    {
        $consultation = $this->consultation;
        if (!self::haveAccessToList($consultation)) {
            return new HtmlErrorResponse(403, \Yii::t('admin', 'no_access'));
        }

        $privilegeScreening = User::havePrivilege($consultation, Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::anyRestriction());
        $privilegeProposals = User::havePrivilege($consultation, Privileges::PRIVILEGE_CHANGE_PROPOSALS, PrivilegeQueryContext::anyRestriction());
        $privilegeDelete = User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_DELETE, PrivilegeQueryContext::anyRestriction());

        $this->activateFunctions();

        if ($motionId === null || $motionId === 'all') {
            $consultation->preloadAllMotionData(Consultation::PRELOAD_ONLY_AMENDMENTS);
        }

        $motionListClass = AdminMotionFilterForm::getClassToUse();

        try {
            if ($privilegeScreening || $privilegeDelete) {
                $this->actionListallScreeningMotions();
                $this->actionListallScreeningAmendments();
            }
            if ($privilegeProposals) {
                $this->actionListallProposalAmendments();
            }
            $motionListClass::performAdditionalListActions($this->consultation);
        } catch (Access $e) {
            throw new ResponseException(new HtmlErrorResponse(403, $e->getMessage()));
        } catch (NotFound $e) {
            throw new ResponseException(new HtmlErrorResponse(404, $e->getMessage()));
        }

        if ($motionId !== null && $motionId !== 'all' && $consultation->getMotion($motionId) === null) {
            $motionId = null;
        }
        if ($motionId === null && $consultation->getSettings()->adminListFilerByMotion) {
            $search = new $motionListClass($consultation, $consultation->motions, $privilegeScreening);
            $search->getSorted(); // Initialize internal fields
            return new HtmlResponse($this->render('motion_list', ['motions' => $consultation->motions, 'search' => $search]));
        }

        if ($motionId !== null && $motionId !== 'all') {
            $motions = [$consultation->getMotion($motionId)];
        } else {
            $motions = $consultation->motions;
        }

        if ($this->isRequestSet('reset')) {
            RequestContext::getSession()->set('motionListSearch' . $consultation->id, null);
            return new RedirectResponse(UrlHelper::createUrl('/admin/motion-list/index'));
        }
        $search = $this->getSearchForm($motions);

        return new HtmlResponse($this->render('list_all', [
            'motionId'           => $motionId,
            'entries'            => $search->getSorted(),
            'search'             => $search,
            'privilegeScreening' => $privilegeScreening,
            'privilegeProposals' => $privilegeProposals,
            'privilegeDelete'    => $privilegeDelete,
        ]));
    }

    private static function getAgendaWithIMotions(Consultation $consultation, IMotionStatusFilter $filter): array
    {
        $ids    = [];
        $result = [];
        $addMotion = function (IMotion $motion) use (&$result, $filter) {
            $result[] = $motion;
            if (is_a($motion, Motion::class)) {
                $result = array_merge($result, $motion->getFilteredAndSortedAmendments($filter));
            }
        };

        $items = ConsultationAgendaItem::getSortedFromConsultation($consultation);
        foreach ($items as $agendaItem) {
            $result[] = $agendaItem;
            $motions  = MotionSorter::getSortedIMotionsFlat($consultation, $agendaItem->getMyIMotions($filter));
            foreach ($motions as $motion) {
                $ids[] = $motion->id;
                $addMotion($motion);
            }
        }
        $result[] = null;

        foreach ($filter->getFilteredConsultationMotions() as $motion) {
            if (!(in_array($motion->id, $ids) || count($motion->getVisibleReplacedByMotions()) > 0)) {
                $addMotion($motion);
            }
        }
        return $result;
    }

    public function actionMotionOdslistall(bool $inactive): BinaryFileResponse
    {
        $filter = IMotionStatusFilter::adminExport($this->consultation, $inactive);
        $items = self::getAgendaWithIMotions($this->consultation, $filter);

        $ods = $this->renderPartial('ods_list_all', [
            'items' => $items,
            'filter' => $filter,
        ]);
        return new BinaryFileResponse(BinaryFileResponse::TYPE_ODS, $ods, true, 'motions');
    }

    public function actionMotionOdslist(string $motionTypeId, bool $textCombined = false, int $inactive = 0): ResponseInterface
    {
        $search = $this->getSearchForm($this->consultation->motions);
        $imotions = $search->getMotionsForExport($this->consultation, $motionTypeId, ($inactive === 1));

        $filename = Tools::sanitizeFilename(\Yii::t('export', 'motions'), false);
        $ods = $this->renderPartial('ods_list', [
            'imotions'     => $imotions,
            'textCombined' => $textCombined,
        ]);
        return new BinaryFileResponse(BinaryFileResponse::TYPE_ODS, $ods, true, $filename);
    }

    public function actionMotionOpenslides(?string $motionTypeId = null, int $version = 1): ResponseInterface
    {
        $search = $this->getSearchForm($this->consultation->motions);
        $imotions = $search->getMotionsForExport($this->consultation, $motionTypeId, false);

        $filename = Tools::sanitizeFilename(\Yii::t('export', 'motions'), false);

        if ($version == 1) {
            $csv = $this->renderPartial('openslides1_list', [
                'motions' => $imotions,
            ]);
        } else {
            $csv = $this->renderPartial('openslides2_list', [
                'motions' => $imotions,
            ]);
        }
        return new BinaryFileResponse(BinaryFileResponse::TYPE_CSV, $csv, true, $filename);
    }

    public function actionMotionCommentsXlsx(string $motionTypeId, int $inactive = 0): ResponseInterface
    {
        $search = $this->getSearchForm($this->consultation->motions);
        $imotions = $search->getMotionsForExport($this->consultation, $motionTypeId, ($inactive === 1));

        $filename = Tools::sanitizeFilename(\Yii::t('export', 'comments'), false);
        $xlsx = $this->renderPartial('xlsx_comments', [
            'imotions'     => $imotions,
        ]);
        return new BinaryFileResponse(BinaryFileResponse::TYPE_XLSX, $xlsx, true, $filename);
    }

    public function actionMotionPdfziplist(?string $motionTypeId = null, int $inactive = 0): ResponseInterface
    {
        $search = $this->getSearchForm($this->consultation->motions);
        $imotions = $search->getMotionsForExport($this->consultation, $motionTypeId, ($inactive === 1));

        $zip = new ZipWriter();
        foreach ($imotions as $imotion) {
            if (!$imotion->getMyMotionType()->hasPdfLayout()) {
                continue;
            }

            $selectedPdfLayout = IPDFLayout::getPdfLayoutForMotionType($imotion->getMyMotionType());
            if (is_a($imotion, Motion::class)) {
                if ($selectedPdfLayout->isHtmlToPdfLayout()) {
                    $file = MotionLayoutHelper::createPdfFromHtml($imotion);
                } elseif ($selectedPdfLayout->latexId !== null) {
                    $file = MotionLayoutHelper::createPdfLatex($imotion);
                } else {
                    $file = MotionLayoutHelper::createPdfTcpdf($imotion);
                }
            } elseif (is_a($imotion, Amendment::class))  {
                if ($selectedPdfLayout->isHtmlToPdfLayout()) {
                    $file = AmendmentLayoutHelper::createPdfFromHtml($imotion);
                } elseif ($selectedPdfLayout->latexId !== null) {
                    $file = AmendmentLayoutHelper::createPdfLatex($imotion);
                } else {
                    $file = AmendmentLayoutHelper::createPdfTcpdf($imotion);
                }
            } else {
                continue;
            }
            $zip->addFile($imotion->getFilenameBase(false) . '.pdf', $file);
        }

        return new BinaryFileResponse(BinaryFileResponse::TYPE_ZIP, $zip->getContentAndFlush(), true, 'motions_pdf');
    }

    public function actionMotionOdtziplist(?string $motionTypeId = null, int $inactive = 0): ResponseInterface
    {
        $search = $this->getSearchForm($this->consultation->motions);
        $imotions = $search->getMotionsForExport($this->consultation, $motionTypeId, ($inactive === 1));

        $zip = new ZipWriter();
        foreach ($imotions as $imotion) {
            if (is_a($imotion, Motion::class)) {
                $doc = $imotion->getMyMotionType()->createOdtTextHandler();
                MotionLayoutHelper::printMotionToOdt($imotion, $doc);
                $zip->addFile($imotion->getFilenameBase(false) . '.odt', $doc->finishAndGetDocument());
            }
            if (is_a($imotion, Amendment::class)) {
                $doc = $imotion->getMyMotionType()->createOdtTextHandler();
                AmendmentLayoutHelper::printAmendmentToOdt($imotion, $doc);
                $zip->addFile($imotion->getFilenameBase(false) . '.odt', $doc->finishAndGetDocument());
            }
        }

        return new BinaryFileResponse(BinaryFileResponse::TYPE_ZIP, $zip->getContentAndFlush(), true, 'motions_odt');
    }

    public function actionMotionOdtall(?string $motionTypeId = null, int $inactive = 0): ResponseInterface
    {
        $search = $this->getSearchForm($this->consultation->motions);
        $imotions = $search->getMotionsForExport($this->consultation, $motionTypeId, ($inactive === 1));

        $doc = $imotions[0]->getMyMotionType()->createOdtTextHandler();

        foreach ($imotions as $i => $imotion) {
            if ($i > 0) {
                $doc->nextPage();
            }
            if (is_a($imotion, Motion::class)) {
                MotionLayoutHelper::printMotionToOdt($imotion, $doc);
            }
            if (is_a($imotion, Amendment::class)) {
                AmendmentLayoutHelper::printAmendmentToOdt($imotion, $doc);
            }
        }

        return new BinaryFileResponse(BinaryFileResponse::TYPE_ODT, $doc->finishAndGetDocument(), true, 'motions');
    }
}
