<?php

namespace app\controllers\admin;

use app\models\exceptions\{Access, NotFound, ExceptionBase, ResponseException};
use app\components\{RequestContext, Tools, UrlHelper, ZipWriter};
use app\models\db\{Amendment, Consultation, IMotion, Motion, User};
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
                } catch (ExceptionBase $e) {} // The user probably just accidentally selected an invalid amendment, so let's just continue
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_proposal_published_pl'));
        }
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

        $motionListClass = AdminMotionFilterForm::class;
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            if ($plugin::getFullMotionListClassOverride()) {
                $motionListClass = $plugin::getFullMotionListClassOverride();
            }
        }

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

        /** @var AdminMotionFilterForm $search */
        $search = new $motionListClass($consultation, $motions, $privilegeScreening);
        if ($this->isRequestSet('reset')) {
            RequestContext::getSession()->set('motionListSearch', null);
            return new RedirectResponse(UrlHelper::createUrl('/admin/motion-list/index'));
        }
        if ($this->getRequestValue('Search')) {
            $attributes = $this->getRequestValue('Search');
            RequestContext::getSession()->set('motionListSearch', $attributes);
            $search->setAttributes($attributes);
        } elseif (RequestContext::getSession()->get('motionListSearch')) {
            $search->setAttributes(RequestContext::getSession()->get('motionListSearch'));
        }

        /** @var AdminMotionFilterForm $search */
        return new HtmlResponse($this->render('list_all', [
            'motionId'           => $motionId,
            'entries'            => $search->getSorted(),
            'search'             => $search,
            'privilegeScreening' => $privilegeScreening,
            'privilegeProposals' => $privilegeProposals,
            'privilegeDelete'    => $privilegeDelete,
        ]));
    }

    public function actionMotionOdslistall(): BinaryFileResponse
    {
        // @TODO: support filtering for motion types and withdrawn motions

        $ods = $this->renderPartial('ods_list_all', [
            'items' => $this->consultation->getAgendaWithIMotions(),
        ]);
        return new BinaryFileResponse(BinaryFileResponse::TYPE_ODS, $ods, true, 'motions');
    }

    public function actionMotionOdslist(int $motionTypeId, bool $textCombined = false, int $withdrawn = 0): ResponseInterface
    {
        $withdrawn = ($withdrawn === 1);

        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            return new HtmlErrorResponse(404, $e->getMessage());
        }

        $imotions = [];
        foreach ($this->consultation->getVisibleIMotionsSorted($withdrawn) as $imotion) {
            if ($imotion->getMyMotionType()->id === $motionTypeId) {
                $imotions[] = $imotion;
            }
        }

        $filename = Tools::sanitizeFilename($motionType->titlePlural, false);
        $ods = $this->renderPartial('ods_list', [
            'imotions'     => $imotions,
            'textCombined' => $textCombined,
            'motionType'   => $motionType,
        ]);
        return new BinaryFileResponse(BinaryFileResponse::TYPE_ODS, $ods, true, $filename);
    }

    public function actionMotionOpenslides(int $motionTypeId, int $version = 1): ResponseInterface
    {
        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            return new HtmlErrorResponse(404, $e->getMessage());
        }


        $filename = Tools::sanitizeFilename($motionType->titlePlural, false);

        $motions = [];
        foreach ($this->consultation->getVisibleIMotionsSorted(false) as $motion) {
            if ($motion->getMyMotionType()->id == $motionTypeId) {
                $motions[] = $motion;
            }
        }

        if ($version == 1) {
            $csv = $this->renderPartial('openslides1_list', [
                'motions' => $motions,
            ]);
        } else {
            $csv = $this->renderPartial('openslides2_list', [
                'motions' => $motions,
            ]);
        }
        return new BinaryFileResponse(BinaryFileResponse::TYPE_CSV, $csv, true, $filename);
    }

    /**
     * @return IMotion[]
     */
    private function getVisibleAmendmentsForExport(int $motionTypeId = 0, int $withdrawn = 0): array
    {
        $withdrawn = ($withdrawn === 1);

        try {
            if ($motionTypeId > 0) {
                $motions = $this->consultation->getMotionType($motionTypeId)->getVisibleMotions($withdrawn);
            } else {
                $motions = $this->consultation->getVisibleMotions($withdrawn);
            }
            if (count($motions) === 0) {
                throw new ResponseException(new HtmlErrorResponse(404, \Yii::t('motion', 'none_yet')));
            }
            /** @var IMotion[] $imotions */
            $imotions = [];
            foreach ($motions as $motion) {
                if ($motion->getMyMotionType()->amendmentsOnly) {
                    $imotions = array_merge($imotions, $motion->getVisibleAmendments($withdrawn));
                } else {
                    $imotions[] = $motion;
                }
            }
        } catch (ExceptionBase $e) {
            throw new ResponseException(new HtmlErrorResponse(404, $e->getMessage()));
        }

        return $imotions;
    }

    public function actionMotionPdfziplist(int $motionTypeId = 0, int $withdrawn = 0): ResponseInterface
    {
        $imotions = $this->getVisibleAmendmentsForExport($motionTypeId, $withdrawn);

        $zip      = new ZipWriter();
        $hasLaTeX = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        foreach ($imotions as $imotion) {
            if (is_a($imotion, Motion::class)) {
                if ($hasLaTeX && $imotion->getMyMotionType()->texTemplateId) {
                    $file = MotionLayoutHelper::createPdfLatex($imotion);
                    $zip->addFile($imotion->getFilenameBase(false) . '.pdf', $file);
                } elseif ($imotion->getMyMotionType()->getPDFLayoutClass()) {
                    $file = MotionLayoutHelper::createPdfTcpdf($imotion);
                    $zip->addFile($imotion->getFilenameBase(false) . '.pdf', $file);
                }
            } elseif (is_a($imotion, Amendment::class))  {
                if ($hasLaTeX && $imotion->getMyMotionType()->texTemplateId) {
                    $file = AmendmentLayoutHelper::createPdfLatex($imotion);
                    $zip->addFile($imotion->getFilenameBase(false) . '.pdf', $file);
                } elseif ($imotion->getMyMotionType()->getPDFLayoutClass()) {
                    $file = AmendmentLayoutHelper::createPdfTcpdf($imotion);
                    $zip->addFile($imotion->getFilenameBase(false) . '.pdf', $file);
                }
            }
        }

        return new BinaryFileResponse(BinaryFileResponse::TYPE_ZIP, $zip->getContentAndFlush(), true, 'motions_pdf');
    }

    public function actionMotionOdtziplist(int $motionTypeId = 0, int $withdrawn = 0): ResponseInterface
    {
        $imotions = $this->getVisibleAmendmentsForExport($motionTypeId, $withdrawn);

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

    public function actionMotionOdtall(int $motionTypeId = 0, int $withdrawn = 0): ResponseInterface
    {
        $imotions = $this->getVisibleAmendmentsForExport($motionTypeId, $withdrawn);

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
