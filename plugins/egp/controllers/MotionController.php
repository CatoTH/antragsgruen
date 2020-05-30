<?php

namespace app\plugins\egp\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\{Motion, User};
use yii\web\Response;

class MotionController extends Base
{
    /**
     * @param string $motionSlug
     *
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionOds($motionSlug)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            \Yii::$app->response->statusCode = 404;

            return 'Motion not found';
        }
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            \Yii::$app->response->statusCode = 403;

            return 'Not permitted to change the tag';
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions.ods');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('@app/plugins/egp/views/motion_ods', [
            'motion' => $motion,
        ]);
    }
}
