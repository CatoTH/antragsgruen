<?php

namespace app\plugins\egp\controllers;

use app\controllers\Base;
use app\models\db\User;
use app\models\settings\Privileges;
use yii\web\Response;

class MotionController extends Base
{
    /**
     * @param string $motionSlug
     *
     * @return string
     * @throws \Yii\base\ExitException
     */
    public function actionOds($motionSlug)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            $this->getHttpResponse()->statusCode = 404;

            return 'Motion not found';
        }
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_SCREENING)) {
            $this->getHttpResponse()->statusCode = 403;

            return 'Not permitted to change the tag';
        }

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        $this->getHttpResponse()->headers->add('Content-Disposition', 'attachment;filename=motions.ods');
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('@app/plugins/egp/views/motion_ods', [
            'motion' => $motion,
        ]);
    }
}
