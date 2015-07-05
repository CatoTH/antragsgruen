<?php

namespace app\controllers\admin;

use app\components\MotionSorter;
use yii\web\Response;

class AmendmentController extends AdminBase
{
    /**
     * @param int $motionId
     * @return string
     */
    public function actionUpdate($motionId)
    {
        // @TODO
    }

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
}
