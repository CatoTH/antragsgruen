<?php

namespace app\controllers\admin;

use app\components\MotionSorter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\exceptions\FormError;
use app\models\forms\AmendmentEditForm;
use app\views\amendment\LayoutHelper;
use yii\web\Response;

class AmendmentController extends AdminBase
{
    /**
     * @param bool $textCombined
     * @return string
     * @throws \app\models\exceptions\NotFound
     */
    public function actionOdslist($textCombined = false)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=amendments.ods');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('ods_list', [
            'motions'      => $this->consultation->getVisibleMotionsSorted(),
            'textCombined' => $textCombined,
        ]);
    }

    /**
     * @return string
     */
    public function actionPdflist()
    {
        return $this->render('pdf_list', ['consultation' => $this->consultation]);
    }

    /**
     * @return string
     */
    public function actionPdfziplist()
    {
        $zip = new \app\components\ZipWriter();
        foreach ($this->consultation->getVisibleMotions() as $motion) {
            foreach ($motion->getVisibleAmendments() as $amendment) {
                $zip->addFile($amendment->getFilenameBase(false) . '.pdf', LayoutHelper::createPdf($amendment));
            }
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/zip');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=amendments_pdf.zip');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }

    /**
     * @return string
     */
    public function actionOdtziplist()
    {
        $zip = new \app\components\ZipWriter();
        foreach ($this->consultation->getVisibleMotions() as $motion) {
            foreach ($motion->getVisibleAmendments() as $amendment) {
                $zip->addFile($amendment->getFilenameBase(false) . '.odt', LayoutHelper::createOdt($amendment));
            }
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/zip');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=amendments_odt.zip');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }

    /**
     * @param int $amendmentId
     * @return string
     */
    public function actionUpdate($amendmentId)
    {
        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment) {
            $this->redirect(UrlHelper::createUrl('admin/motion/listall'));
        }
        $this->checkConsistency($amendment->getMyMotion(), $amendment);

        $this->layout = 'column2';

        $post = \Yii::$app->request->post();
        $form = new AmendmentEditForm($amendment->getMyMotion(), $amendment);
        $form->setAdminMode(true);

        if ($this->isPostSet('screen') && $amendment->status == Amendment::STATUS_SUBMITTED_UNSCREENED) {
            if ($amendment->getMyMotion()->findAmendmentWithPrefix($post['titlePrefix'], $amendment)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'amend_prefix_collission'));
            } else {
                $amendment->status      = Amendment::STATUS_SUBMITTED_SCREENED;
                $amendment->titlePrefix = $post['titlePrefix'];
                $amendment->save();
                $amendment->onPublish();
                \yii::$app->session->setFlash('success', \Yii::t('admin', 'amend_screened'));
            }
        }

        if ($this->isPostSet('delete')) {
            $amendment->status = Amendment::STATUS_DELETED;
            $amendment->save();
            $amendment->getMyMotion()->flushCacheStart();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'amend_deleted'));
            $this->redirect(UrlHelper::createUrl('admin/motion/listall'));
            return '';
        }

        if ($this->isPostSet('save')) {
            if (!isset($post['edittext'])) {
                unset($post['sections']);
            }
            $form->setAttributes([$post, $_FILES]);
            try {
                $form->saveAmendment($amendment);
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }

            $amdat                     = $post['amendment'];
            $amendment->statusString   = $amdat['statusString'];
            $amendment->dateCreation   = Tools::dateBootstraptime2sql($amdat['dateCreation']);
            $amendment->noteInternal   = $amdat['noteInternal'];
            $amendment->status         = $amdat['status'];
            $amendment->dateResolution = '';
            if ($amdat['dateResolution'] != '') {
                $amendment->dateResolution = Tools::dateBootstraptime2sql($amdat['dateResolution']);
            }

            if ($amendment->getMyMotion()->findAmendmentWithPrefix($amdat['titlePrefix'], $amendment)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'amend_prefix_collission'));
            } else {
                $amendment->titlePrefix = $post['amendment']['titlePrefix'];
            }
            $amendment->save();
            $amendment->getMyMotion()->flushCacheWithChildren();
            $amendment->refresh();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'saved'));

        }

        return $this->render('update', ['amendment' => $amendment, 'form' => $form]);
    }

    /**
     * @return string
     */
    public function actionOpenslides()
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'text/csv');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=Amendments.csv');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        $amendments = [];
        foreach ($this->consultation->getVisibleMotionsSorted(false) as $motion) {
            foreach ($motion->getVisibleAmendmentsSorted(false) as $amendment) {
                $amendments[] = $amendment;
            }
        }

        return $this->renderPartial('openslides_list', [
            'amendments' => $amendments,
        ]);
    }
}
