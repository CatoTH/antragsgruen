<?php

namespace app\controllers\admin;

use app\components\ZipWriter;
use app\models\db\User;
use app\models\exceptions\ExceptionBase;
use app\models\forms\AdminMotionFilterForm;
use app\models\settings\AntragsgruenApp;
use app\views\motion\LayoutHelper;
use yii\web\Response;

class MotionListController extends AdminBase
{
    /**
     */
    protected function actionListallScreeningMotions()
    {
        if ($this->isRequestSet('motionScreen')) {
            $motion = $this->consultation->getMotion($this->getRequestValue('motionScreen'));
            if (!$motion) {
                return;
            }
            $motion->setScreened();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_screened'));
        }
        if ($this->isRequestSet('motionUnscreen')) {
            $motion = $this->consultation->getMotion($this->getRequestValue('motionUnscreen'));
            if (!$motion) {
                return;
            }
            $motion->setUnscreened();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_unscreened'));
        }
        if ($this->isRequestSet('motionDelete')) {
            $motion = $this->consultation->getMotion($this->getRequestValue('motionDelete'));
            if (!$motion) {
                return;
            }
            $motion->setDeleted();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_deleted'));
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
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_screened_pl'));
        }

        if ($this->isRequestSet('unscreen')) {
            foreach ($this->getRequestValue('motions') as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->setUnscreened();
            }
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_unscreened_pl'));
        }

        if ($this->isRequestSet('delete')) {
            foreach ($this->getRequestValue('motions') as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->setDeleted();
            }
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_deleted_pl'));
        }
    }


    /**
     */
    protected function actionListallScreeningAmendments()
    {
        if ($this->isRequestSet('amendmentScreen')) {
            $amendment = $this->consultation->getAmendment($this->getRequestValue('amendmentScreen'));
            if (!$amendment) {
                return;
            }
            $amendment->setScreened();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_am_screened'));
        }
        if ($this->isRequestSet('amendmentUnscreen')) {
            $amendment = $this->consultation->getAmendment($this->getRequestValue('amendmentUnscreen'));
            if (!$amendment) {
                return;
            }
            $amendment->setUnscreened();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_am_unscreened'));
        }
        if ($this->isRequestSet('amendmentDelete')) {
            $amendment = $this->consultation->getAmendment($this->getRequestValue('amendmentDelete'));
            if (!$amendment) {
                return;
            }
            $amendment->setDeleted();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_am_deleted'));
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
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_am_screened_pl'));
        }

        if ($this->isRequestSet('unscreen')) {
            foreach ($this->getRequestValue('amendments') as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->setUnscreened();
            }
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_am_unscreened_pl'));
        }

        if ($this->isRequestSet('delete')) {
            foreach ($this->getRequestValue('amendments') as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->setDeleted();
            }
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_am_deleted_pl'));
        }
    }

    /**
     */
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
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_proposal_published_pl'));
        }
    }


    /**
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionIndex()
    {
        $consultation       = $this->consultation;
        $privilegeScreening = User::havePrivilege($consultation, User::PRIVILEGE_SCREENING);
        $privilegeProposals = User::havePrivilege($consultation, User::PRIVILEGE_CHANGE_PROPOSALS);
        if (!($privilegeScreening || $privilegeProposals)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_acccess'));
            return '';
        }

        $consultation->preloadAllMotionData();

        if ($privilegeScreening) {
            $this->actionListallScreeningMotions();
            $this->actionListallScreeningAmendments();
        }
        if ($privilegeProposals) {
            $this->actionListallProposalAmendments();
        }

        $search = new AdminMotionFilterForm($consultation, $consultation->motions, true, $privilegeScreening);
        if ($this->isRequestSet('Search')) {
            $search->setAttributes($this->getRequestValue('Search'));
        }

        return $this->render('list_all', [
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

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions.ods');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('ods_list_all', [
            'items' => $this->consultation->getAgendaWithMotions(),
        ]);
    }

    /**
     * @param int $motionTypeId
     * @param bool $textCombined
     * @param int $withdrawn
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionMotionOdslist($motionTypeId, $textCombined = false, $withdrawn = 0)
    {
        $withdrawn = ($withdrawn == 1);

        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions.ods');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        $motions = [];
        foreach ($this->consultation->getVisibleMotionsSorted($withdrawn) as $motion) {
            if ($motion->motionTypeId == $motionTypeId) {
                $motions[] = $motion;
            }
        }

        return $this->renderPartial('ods_list', [
            'motions'      => $motions,
            'textCombined' => $textCombined,
            'motionType'   => $motionType,
        ]);
    }

    /**
     * @param int $motionTypeId
     * @param bool $textCombined
     * @param int $withdrawn
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionMotionExcellist($motionTypeId, $textCombined = false, $withdrawn = 0)
    {
        if (!AntragsgruenApp::hasPhpExcel()) {
            return $this->showErrorpage(500, 'The Excel package has not been installed. ' .
                'To install it, execute "./composer.phar require phpoffice/phpexcel".');
        }

        $withdrawn = ($withdrawn == 1);

        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        defined('PCLZIP_TEMPORARY_DIR') or define('PCLZIP_TEMPORARY_DIR', $this->getParams()->getTmpDir());

        $excelMime                   = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', $excelMime);
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions.xlsx');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        error_reporting(E_ALL & ~E_DEPRECATED); // PHPExcel ./. PHP 7

        $motions = [];
        foreach ($this->consultation->getVisibleMotionsSorted($withdrawn) as $motion) {
            if ($motion->motionTypeId == $motionTypeId) {
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
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionMotionOpenslides($motionTypeId, $version = 1)
    {
        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        $filename                    = rawurlencode($motionType->titlePlural);
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'text/csv');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=' . $filename . '.csv');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        $motions = [];
        foreach ($this->consultation->getVisibleMotionsSorted(false) as $motion) {
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
     * @return string
     * @throws \yii\base\ExitException
     * @throws \app\models\exceptions\Internal
     */
    public function actionMotionPdfziplist($motionTypeId = 0, $withdrawn = 0)
    {
        $withdrawn = ($withdrawn == 1);

        try {
            if ($motionTypeId > 0) {
                $motions = $this->consultation->getMotionType($motionTypeId)->getVisibleMotions($withdrawn);
            } else {
                $motions = $this->consultation->getVisibleMotions($withdrawn);
            }
            if (count($motions) == 0) {
                return $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
            }
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        $zip = new ZipWriter();
        foreach ($motions as $motion) {
            if ($this->getParams()->xelatexPath && $motion->getMyMotionType()->texTemplateId) {
                $file = LayoutHelper::createPdfLatex($motion);
            } else {
                $file = LayoutHelper::createPdfTcpdf($motion);
            }
            $zip->addFile($motion->getFilenameBase(false) . '.pdf', $file);
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/zip');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions_pdf.zip');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }

    /**
     * @param int $motionTypeId
     * @param int $withdrawn
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionMotionOdtziplist($motionTypeId = 0, $withdrawn = 0)
    {
        $withdrawn = ($withdrawn == 1);

        try {
            if ($motionTypeId > 0) {
                $motions = $this->consultation->getMotionType($motionTypeId)->getVisibleMotions($withdrawn);
            } else {
                $motions = $this->consultation->getVisibleMotions($withdrawn);
            }
            if (count($motions) == 0) {
                return $this->showErrorpage(404, \Yii::t('motion', 'none_yet'));
            }
        } catch (ExceptionBase $e) {
            return $this->showErrorpage(404, $e->getMessage());
        }

        $zip = new ZipWriter();
        foreach ($motions as $motion) {
            $content = $this->renderPartial('@app/views/motion/view_odt', ['motion' => $motion]);
            $zip->addFile($motion->getFilenameBase(false) . '.odt', $content);
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/zip');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions_odt.zip');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }
}
