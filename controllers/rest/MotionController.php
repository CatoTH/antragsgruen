<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\components\Tools;
use app\models\api\imotion\MotionDetails;
use app\models\exceptions\NotFound;
use app\models\http\RestApiResponse;

class MotionController extends RestBase
{
    public function actionGet(string $motionSlug, ?string $lineNumbers = null): RestApiResponse
    {
        $this->handleRestHeaders(['GET']);

        $lineNumbers = ($lineNumbers !== null && in_array(strtolower($lineNumbers), ['true', '1']));

        try {
            $motion = $this->getMotionWithCheck($motionSlug, true);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        if (!$motion->isReadable()) {
            return $this->returnRestResponseFromException(new NotFound('Motion is not readable'));
        }

        $motionDto = MotionDetails::fromEntity($motion, $lineNumbers);

        return new RestApiResponse(200, null, Tools::getSerializer()->serialize($motionDto, 'json'));
    }
}
