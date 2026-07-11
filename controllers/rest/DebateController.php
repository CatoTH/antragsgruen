<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\models\api\debate\DebateState;
use app\models\exceptions\NotFound;
use app\models\http\RestApiResponse;

class DebateController extends RestBase
{
    public function actionGet(): RestApiResponse
    {
        // Always enabled: the "Currently debated" widget polls this endpoint for all visitors,
        // independently of whether general API access is enabled for the site
        $this->handleRestHeaders(['GET'], true);

        if (!$this->consultation || !$this->consultation->getSettings()->hasCurrentlyDebated) {
            return $this->returnRestResponseFromException(
                new NotFound('The "Currently debated" feature is not enabled for this consultation', 404)
            );
        }

        return $this->createResponse(200, DebateState::fromConsultation($this->consultation));
    }
}
