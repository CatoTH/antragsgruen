<?php

namespace app\controllers\admin;

use app\components\Tools;
use app\components\ZipWriter;
use app\models\db\{Amendment, Consultation, IMotion, Motion, User};
use app\models\exceptions\ExceptionBase;
use app\models\forms\AdminMotionFilterForm;
use app\models\settings\AntragsgruenApp;
use app\models\settings\Privileges;
use app\views\amendment\LayoutHelper as AmendmentLayoutHelper;
use app\views\motion\LayoutHelper as MotionLayoutHelper;
use yii\web\Response;

class MotionListController extends AdminBase
{
    protected function actionListallScreeningMotions()
    {
        if ($this->isRequestSet('motionScreen')) {
            $motion = $this->consultation->getMotion($this->getRequestValue('motionScreen'));
            if (!$motion) {
                return;
            }
            $motion->setScreened();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_screened'));
        }
        if ($this->isRequestSet('motionUnscreen')) {
            $motion = $this->consultation->getMotion($this->getRequestValue('motionUnscreen'));
            if (!$motion) {
                return;
            }
            $motion->setUnscreened();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_unscreened'));
        }
        if ($this->isRequestSet('motionDelete')) {
            $motion = $this->consultation->getMotion($this->getRequestValue('motionDelete'));
            if (!$motion) {
                return;
            }
            $motion->setDeleted();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_deleted'));
        }

        if (!$this->isRequestSet('motions') || !$this->isRequestSet('save')) {
            return;
        }
        if ($this->isRequestSet('screen')) {
            foreach ($this->getRequestValue('motions') as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->setScreened();
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_screened_pl'));
        }

        if ($this->isRequestSet('unscreen')) {
            foreach ($this->getRequestValue('motions') as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->setUnscreened();
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_unscreened_pl'));
        }

        if ($this->isRequestSet('delete')) {
            foreach ($this->getRequestValue('motions') as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->setDeleted();
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_deleted_pl'));
        }
    }

    protected function actionListallScreeningAmendments()
    {
        if ($this->isRequestSet('amendmentScreen')) {
            $amendment = $this->consultation->getAmendment($this->getRequestValue('amendmentScreen'));
            if (!$amendment) {
                return;
            }
            $amendment->setScreened();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_am_screened'));
        }
        if ($this->isRequestSet('amendmentUnscreen')) {
            $amendment = $this->consultation->getAmendment($this->getRequestValue('amendmentUnscreen'));
            if (!$amendment) {
                return;
            }
            $amendment->setUnscreened();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_am_unscreened'));
        }
        if ($this->isRequestSet('amendmentDelete')) {
            $amendment = $this->consultation->getAmendment($this->getRequestValue('amendmentDelete'));
            if (!$amendment) {
                return;
            }
            $amendment->setDeleted();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_am_deleted'));
        }
        if (!$this->isRequestSet('amendments') || !$this->isRequestSet('save')) {
            return;
        }
        if ($this->isRequestSet('screen')) {
            foreach ($this->getRequestValue('amendments') as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->setScreened();
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_am_screened_pl'));
        }

        if ($this->isRequestSet('unscreen')) {
            foreach ($this->getRequestValue('amendments') as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->setUnscreened();
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_am_unscreened_pl'));
        }

        if ($this->isRequestSet('delete')) {
            foreach ($this->getRequestValue('amendments') as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->setDeleted();
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_am_deleted_pl'));
        }
    }

    protected function actionListallProposalAmendments()
    {
        if ($this->isRequestSet('proposalVisible')) {
            foreach ($this->getRequestValue('amendments', []) as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->setProposalPublished();
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'list_proposal_published_pl'));
        }
    }


    public function actionIndex(?string $motionId = null): string
    {
        $consultation       = $this->consultation;
        $privilegeScreening = User::havePrivilege($consultation, Privileges::PRIVILEGE_SCREENING);
        $privilegeProposals = User::havePrivilege($consultation, Privileges::PRIVILEGE_CHANGE_PROPOSALS);
        if (!($privilegeScreening || $privilegeProposals)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_acccess'));

            return '';
        }

        $this->activateFunctions();

        if ($motionId === null || $motionId === 'all') {
            $consultation->preloadAllMotionData(Consultation::PRELOAD_ONLY_AMENDMENTS);
        }

        if ($privilegeScreening) {
            $this->actionListallScreeningMotions();
            $this->actionListallScreeningAmendments();
        }
        if ($privilegeProposals) {
            $this->actionListallProposalAmendments();
        }

        if ($motionId !== null && $motionId !== 'all' && $consultation->getMotion($motionId) === null) {
            $motionId = null;
        }
        if ($motionId === null && $consultation->getSettings()->adminListFilerByMotion) {
            $search = new AdminMotionFilterForm($consultation, $consultation->motions, true, $privilegeScreening);
            return $this->render('motion_list', ['motions' => $consultation->motions, 'search' => $search]);
        }

        if ($motionId !== null && $motionId !== 'all') {
            $motions = [$consultation->getMotion($motionId)];
        } else {
            $motions = $consultation->motions;
        }

        $search = new AdminMotionFilterForm($consultation, $motions, true, $privilegeScreening);
        if ($this->isRequestSet('Search')) {
            $search->setAttributes($this->getRequestValue('Search'));
        }

        return $this->render('list_all', [
            'motionId'           => $motionId,
            'entries'            => $search->getSorted(),
            'search'             => $search,
            'privilegeScreening' => $privilegeScreening,
            'privilegeProposals' => $privilegeProposals,
        ]);
    }

    /**
     * @return string
     */
    public function actionMotionOdslistall()
    {
        // @TODO: support filtering for motion types and withdrawn motions

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        $this->getHttpResponse()->headers->add('Content-Disposition', 'attachment;filename=motions.ods');
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('ods_list_all', [
            'items' => $this->consultation->getAgendaWithIMotions(),
        ]);
    }

    /**
     * @param int $motionTypeId
     * @param bool $textCombined
     * @param int $withdrawn
     *
     * @return string
     * @throws \Yii\base\ExitException
     */
    public function actionMotionOdslist($motionTypeId, $textCombined = false, $withdrawn = 0)
    {
        $withdrawn    = ($withdrawn == 1);
        $motionTypeId = intval($motionTypeId);

        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            $this->showErrorpage(404, $e->getMessage());
            return '';
        }

        $filename = Tools::sanitizeFilename($motionType->titlePlural, false) . '.ods';
        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        $this->getHttpResponse()->headers->add('Content-Disposition', 'attachment;filename=' . $filename);
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        $imotions = [];
        foreach ($this->consultation->getVisibleIMotionsSorted($withdrawn) as $imotion) {
            if ($imotion->getMyMotionType()->id === $motionTypeId) {
                $imotions[] = $imotion;
            }
        }

        return $this->renderPartial('ods_list', [
            'imotions'     => $imotions,
            'textCombined' => $textCombined,
            'motionType'   => $motionType,
        ]);
    }

    /**
     * @param int $motionTypeId
     * @param bool $textCombined
     * @param int $withdrawn
     *
     * @return string
     * @throws \Yii\base\ExitException
     */
    public function actionMotionExcellist($motionTypeId, $textCombined = false, $withdrawn = 0)
    {
        $motionTypeId = intval($motionTypeId);

        if (!AntragsgruenApp::hasPhpExcel()) {
            $this->showErrorpage(500, 'The Excel package has not been installed. ' .
                                             'To install it, execute "./composer.phar require phpoffice/phpexcel".');
            return '';
        }

        $withdrawn = ($withdrawn == 1);

        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            $this->showErrorpage(404, $e->getMessage());
            return '';
        }

        defined('PCLZIP_TEMPORARY_DIR') or define('PCLZIP_TEMPORARY_DIR', $this->getParams()->getTmpDir());

        $excelMime                   = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', $excelMime);
        $this->getHttpResponse()->headers->add('Content-Disposition', 'attachment;filename=motions.xlsx');
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        error_reporting(E_ALL & ~E_DEPRECATED); // PHPExcel ./. PHP 7

        $motions = [];
        foreach ($this->consultation->getVisibleIMotionsSorted($withdrawn) as $motion) {
            if (is_a($motion, Motion::class) && $motion->motionTypeId == $motionTypeId) {
                $motions[] = $motion;
            }
        }

        return $this->renderPartial('excel_list', [
            'motions'      => $motions,
            'textCombined' => $textCombined,
            'motionType'   => $motionType,
        ]);
    }

    /**
     * @param int $motionTypeId
     * @param int $version
     *
     * @return string
     * @throws \Yii\base\ExitException
     */
    public function actionMotionOpenslides($motionTypeId, $version = 1)
    {
        $motionTypeId = intval($motionTypeId);

        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            $this->showErrorpage(404, $e->getMessage());
            return '';
        }

        $filename                    = rawurlencode($motionType->titlePlural);
        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'text/csv');
        $this->getHttpResponse()->headers->add('Content-Disposition', 'attachment;filename=' . $filename . '.csv');
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        $motions = [];
        foreach ($this->consultation->getVisibleIMotionsSorted(false) as $motion) {
            if ($motion->motionTypeId == $motionTypeId) {
                $motions[] = $motion;
            }
        }

        if ($version == 1) {
            return $this->renderPartial('openslides1_list', [
                'motions' => $motions,
            ]);
        } else {
            return $this->renderPartial('openslides2_list', [
                'motions' => $motions,
            ]);
        }
    }

    /**
     * @param int $motionTypeId
     * @param int $withdrawn
     *
     * @return string
     * @throws \Yii\base\ExitException
     * @throws \app\models\exceptions\Internal
     */
    public function actionMotionPdfziplist($motionTypeId = 0, $withdrawn = 0)
    {
        $withdrawn    = ($withdrawn == 1);
        $motionTypeId = intval($motionTypeId);

        try {
            if ($motionTypeId > 0) {
                $motions = $this->consultation->getMotionType($motionTypeId)->getVisibleMotions($withdrawn);
            } else {
                $motions = $this->consultation->getVisibleMotions($withdrawn);
            }
            if (count($motions) === 0) {
                $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
                return '';
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
            $this->showErrorpage(404, $e->getMessage());
            return '';
        }

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

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/zip');
        $this->getHttpResponse()->headers->add('Content-Disposition', 'attachment;filename=motions_pdf.zip');
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }

    /**
     * @param int $motionTypeId
     * @param int $withdrawn
     *
     * @return string
     * @throws \Yii\base\ExitException
     */
    public function actionMotionOdtziplist($motionTypeId = 0, $withdrawn = 0)
    {
        $withdrawn    = ($withdrawn == 1);
        $motionTypeId = intval($motionTypeId);

        try {
            if ($motionTypeId > 0) {
                $motions = $this->consultation->getMotionType($motionTypeId)->getVisibleMotions($withdrawn);
            } else {
                $motions = $this->consultation->getVisibleMotions($withdrawn);
            }
            if (count($motions) === 0) {
                $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
                return '';
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
            $this->showErrorpage(404, $e->getMessage());
            return '';
        }

        $zip = new ZipWriter();
        foreach ($imotions as $imotion) {
            if (is_a($imotion, Motion::class)) {
                $content = $this->renderPartial('@app/views/motion/view_odt', ['motion' => $imotion]);
                $zip->addFile($imotion->getFilenameBase(false) . '.odt', $content);
            }
            if (is_a($imotion, Amendment::class)) {
                $content = $this->renderPartial('@app/views/amendment/view_odt', ['amendment' => $imotion]);
                $zip->addFile($imotion->getFilenameBase(false) . '.odt', $content);
            }
        }

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/zip');
        $this->getHttpResponse()->headers->add('Content-Disposition', 'attachment;filename=motions_odt.zip');
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }
}
