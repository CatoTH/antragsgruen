<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\models\api\imotion\AmendmentDetails;
use app\models\exceptions\NotFound;
use app\models\http\RestApiResponse;

class AmendmentController extends RestBase
{
    public function actionGet(string $motionSlug, int $amendmentId): RestApiResponse
    {
        $this->handleRestHeaders(['GET']);

        try {
            $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId, null, true);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        if (!$amendment->isReadable()) {
            return $this->returnRestResponseFromException(new NotFound('Amendment is not readable', 404));
        }

        return $this->createResponse(200, AmendmentDetails::fromEntity($amendment));
    }
}
