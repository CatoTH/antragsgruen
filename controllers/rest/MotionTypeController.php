<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\components\Tools;
use app\models\api\motionType\MotionTypeList;
use app\models\http\RestApiResponse;

class MotionTypeController extends RestBase
{
    public function actionIndex(): RestApiResponse
    {
        $this->handleRestHeaders(['GET']);

        $list = MotionTypeList::fromConsultation($this->consultation);

        return new RestApiResponse(200, null, Tools::getSerializer()->serialize($list, 'json'));
    }
}
