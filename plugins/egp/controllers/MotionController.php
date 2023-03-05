<?php

namespace app\plugins\egp\controllers;

use app\controllers\Base;
use app\models\db\User;
use app\models\http\BinaryFileResponse;
use app\models\http\HtmlErrorResponse;
use app\models\http\ResponseInterface;
use app\models\settings\PrivilegeQueryContext;
use app\models\settings\Privileges;
use yii\web\Response;

class MotionController extends Base
{
    public function actionOds(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404, 'Motion not found');
        }
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::motion($motion))) {
            return new HtmlErrorResponse(403, 'Not permitted to download ODS');
        }

        $ods = $this->renderPartial('@app/plugins/egp/views/motion_ods', [
            'motion' => $motion,
        ]);
        return new BinaryFileResponse(BinaryFileResponse::TYPE_ODS, $ods, true, 'motion');
    }
}
