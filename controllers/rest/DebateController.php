<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\models\api\debate\{DebateSelectables, DebateState};
use app\models\db\User;
use app\models\exceptions\NotFound;
use app\models\http\{RestApiExceptionResponse, RestApiResponse};
use app\models\settings\Privileges;

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

    public function actionSelectable(): RestApiResponse
    {
        // Always enabled: the debate moderation widget is used from the homepage,
        // independently of whether general API access is enabled for the site
        $this->handleRestHeaders(['GET'], true);

        if (!$this->consultation || !$this->consultation->getSettings()->hasCurrentlyDebated) {
            return $this->returnRestResponseFromException(
                new NotFound('The "Currently debated" feature is not enabled for this consultation', 404)
            );
        }

        if (!User::getCurrentUser()) {
            return new RestApiExceptionResponse(401, 'Not authenticated');
        }
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_DEBATE_MODERATION, null)) {
            return new RestApiExceptionResponse(403, 'Missing privilege to moderate debates');
        }

        return $this->createResponse(200, DebateSelectables::fromConsultation($this->consultation));
    }
}
