<?php

namespace app\controllers\admin;

use app\components\MotionSorter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\exceptions\FormError;
use app\models\forms\AmendmentEditForm;
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
        @ini_set('memory_limit', '256M');

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=amendments.ods');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        $motions = MotionSorter::getSortedMotionsFlat($this->consultation, $this->consultation->motions);

        return $this->renderPartial('ods_list', [
            'motions'      => $motions,
            'textCombined' => $textCombined,
        ]);
    }

    /**
     * @return string
     */
    public function actionPdflist()
    {
        $motions = MotionSorter::getSortedMotionsFlat($this->consultation, $this->consultation->motions);
        return $this->render('pdf_list', ['motions' => $motions]);
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
        $this->checkConsistency($amendment->motion, $amendment);

        $this->layout = 'column2';

        $form = new AmendmentEditForm($amendment->motion, $amendment);
        $form->setAdminMode(true);

        if (isset($_POST['screen']) && $amendment->status == Amendment::STATUS_SUBMITTED_UNSCREENED) {
            if ($amendment->motion->findAmendmentWithPrefix($_POST['titlePrefix'], $amendment)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'amend_prefix_collission'));
            } else {
                $amendment->status      = Amendment::STATUS_SUBMITTED_SCREENED;
                $amendment->titlePrefix = $_POST['titlePrefix'];
                $amendment->save();
                $amendment->onPublish();
                \yii::$app->session->setFlash('success', \Yii::t('admin', 'amend_screened'));
            }
        }

        if (isset($_POST['delete'])) {
            $amendment->status = Amendment::STATUS_DELETED;
            $amendment->save();
            $amendment->motion->flushCacheStart();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'amend_deleted'));
            $this->redirect(UrlHelper::createUrl('admin/motion/listall'));
            return '';
        }

        if (isset($_POST['save'])) {
            $post = $_POST;
            if (!isset($_POST['edittext'])) {
                unset($post['sections']);
            }
            $form->setAttributes([$post, $_FILES]);
            try {
                $form->saveAmendment($amendment);
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }

            $amdat                     = $_POST['amendment'];
            $amendment->statusString   = $amdat['statusString'];
            $amendment->dateCreation   = Tools::dateBootstraptime2sql($amdat['dateCreation']);
            $amendment->noteInternal   = $amdat['noteInternal'];
            $amendment->status         = $amdat['status'];
            $amendment->dateResolution = '';
            if ($amdat['dateResolution'] != '') {
                $amendment->dateResolution = Tools::dateBootstraptime2sql($amdat['dateResolution']);
            }

            if ($amendment->motion->findAmendmentWithPrefix($amdat['titlePrefix'], $amendment)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'amend_prefix_collission'));
            } else {
                $amendment->titlePrefix = $_POST['amendment']['titlePrefix'];
            }
            $amendment->save();
            $amendment->motion->flushCacheWithChildren();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'saved'));
        }

        return $this->render('update', ['amendment' => $amendment, 'form' => $form]);
    }
}
