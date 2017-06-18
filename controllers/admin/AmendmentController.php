<?php

namespace app\controllers\admin;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\exceptions\FormError;
use app\models\forms\AmendmentEditForm;
use app\views\amendment\LayoutHelper;
use yii\web\Response;

class AmendmentController extends AdminBase
{
    /**
     * @param bool $textCombined
     * @param int $withdrawn
     * @return string
     */
    public function actionOdslist($textCombined = false, $withdrawn = 0)
    {
        $withdrawn = ($withdrawn == 1);

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=amendments.ods');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('ods_list', [
            'motions'      => $this->consultation->getVisibleMotionsSorted($withdrawn),
            'textCombined' => $textCombined,
            'withdrawn'    => $withdrawn,
        ]);
    }

    /**
     * @param int $withdrawn
     * @return string
     */
    public function actionPdflist($withdrawn = 0)
    {
        $withdrawn = ($withdrawn == 1);
        return $this->render('pdf_list', ['consultation' => $this->consultation, 'withdrawn' => $withdrawn]);
    }

    /**
     * @param int $withdrawn
     * @return string
     */
    public function actionPdfziplist($withdrawn = 0)
    {
        $withdrawn = ($withdrawn == 1);
        $zip       = new \app\components\ZipWriter();
        foreach ($this->consultation->getVisibleMotions($withdrawn) as $motion) {
            foreach ($motion->getVisibleAmendments($withdrawn) as $amendment) {
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
     * @param int $withdrawn
     * @return string
     */
    public function actionOdtziplist($withdrawn = 0)
    {
        $withdrawn = ($withdrawn == 1);
        $zip       = new \app\components\ZipWriter();
        foreach ($this->consultation->getVisibleMotions($withdrawn) as $motion) {
            foreach ($motion->getVisibleAmendments($withdrawn) as $amendment) {
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
     * @param Amendment $amendment
     */
    private function saveAmendmentSupporters(Amendment $amendment)
    {
        $names         = \Yii::$app->request->post('supporterName', []);
        $orgas         = \Yii::$app->request->post('supporterOrga', []);
        $preIds        = \Yii::$app->request->post('supporterId', []);
        $newSupporters = [];
        /** @var AmendmentSupporter[] $preSupporters */
        $preSupporters = [];
        foreach ($amendment->getSupporters() as $supporter) {
            $preSupporters[$supporter->id] = $supporter;
        }
        for ($i = 0; $i < count($names); $i++) {
            if (trim($names[$i]) == '' && trim($orgas[$i]) == '') {
                continue;
            }
            if (isset($preSupporters[$preIds[$i]])) {
                $supporter = $preSupporters[$preIds[$i]];
            } else {
                $supporter              = new AmendmentSupporter();
                $supporter->amendmentId = $amendment->id;
                $supporter->role        = AmendmentSupporter::ROLE_SUPPORTER;
                $supporter->personType  = AmendmentSupporter::PERSON_NATURAL;
            }
            $supporter->name         = $names[$i];
            $supporter->organization = $orgas[$i];
            $supporter->position     = $i;
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

        if ($this->isPostSet('screen') && $amendment->isInScreeningProcess()) {
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

            $amdat                        = $post['amendment'];
            $amendment->statusString      = $amdat['statusString'];
            $amendment->dateCreation      = Tools::dateBootstraptime2sql($amdat['dateCreation']);
            $amendment->noteInternal      = $amdat['noteInternal'];
            $amendment->status            = $amdat['status'];
            $amendment->globalAlternative = (isset($amdat['globalAlternative']) ? 1 : 0);
            $amendment->dateResolution    = '';
            if ($amdat['dateResolution'] != '') {
                $amendment->dateResolution = Tools::dateBootstraptime2sql($amdat['dateResolution']);
            }

            if ($amendment->getMyMotion()->findAmendmentWithPrefix($amdat['titlePrefix'], $amendment)) {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'amend_prefix_collission'));
            } else {
                $amendment->titlePrefix = $post['amendment']['titlePrefix'];
            }
            $amendment->save();

            $this->saveAmendmentSupporters($amendment);

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
