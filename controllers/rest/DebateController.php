<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\components\{DebateTools, Tools};
use app\models\api\debate\{DebateItemTargetType, DebateSelectables, DebateStartRequest, DebateState};
use app\models\db\{ConsultationAgendaItem, User};
use app\models\exceptions\NotFound;
use app\models\http\{RestApiExceptionResponse, RestApiResponse};
use app\models\settings\Privileges;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;

class DebateController extends RestBase
{
    public function actionIndex(): RestApiResponse
    {
        // Always enabled: the "Currently debated" widget polls this endpoint for all visitors,
        // independently of whether general API access is enabled for the site
        $this->handleRestHeaders(['GET', 'PUT', 'DELETE'], true);

        if (!$this->consultation || !$this->consultation->getSettings()->hasCurrentlyDebated) {
            return $this->returnRestResponseFromException(
                new NotFound('The "Currently debated" feature is not enabled for this consultation', 404)
            );
        }

        if ($this->getHttpMethod() === 'PUT') {
            return $this->startDebate();
        }
        if ($this->getHttpMethod() === 'DELETE') {
            return $this->endDebate();
        }

        return $this->createResponse(200, DebateState::fromConsultation($this->consultation));
    }

    private function startDebate(): RestApiResponse
    {
        if ($error = $this->getModerationPermissionError()) {
            return $error;
        }

        try {
            /** @var DebateStartRequest $request */
            $request = Tools::getSerializer()->deserialize($this->getPostBody(), DebateStartRequest::class, 'json');
        } catch (SerializerException $e) {
            return new RestApiExceptionResponse(400, 'Invalid request body: ' . $e->getMessage());
        }

        $target = match ($request->targetType) {
            DebateItemTargetType::MOTION => $this->consultation->getMotion($request->targetId),
            DebateItemTargetType::AMENDMENT => $this->consultation->getAmendment($request->targetId),
            DebateItemTargetType::AGENDA_ITEM => $this->consultation->getAgendaItem($request->targetId),
        };
        if ($target === null || (!is_a($target, ConsultationAgendaItem::class) && (!$target->isVisible() || $target->isDeleted()))) {
            return $this->returnRestResponseFromException(new NotFound('The item to be debated was not found', 404));
        }

        DebateTools::startDebate($this->consultation, $target);

        return $this->createResponse(200, DebateState::fromConsultation($this->consultation));
    }

    private function endDebate(): RestApiResponse
    {
        if ($error = $this->getModerationPermissionError()) {
            return $error;
        }

        DebateTools::endDebate($this->consultation);

        return $this->createResponse(200, DebateState::fromConsultation($this->consultation));
    }

    private function getModerationPermissionError(): ?RestApiExceptionResponse
    {
        if (!User::getCurrentUser()) {
            return new RestApiExceptionResponse(401, 'Not authenticated');
        }
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_DEBATE_MODERATION, null)) {
            return new RestApiExceptionResponse(403, 'Missing privilege to moderate debates');
        }

        return null;
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

        if ($error = $this->getModerationPermissionError()) {
            return $error;
        }

        return $this->createResponse(200, DebateSelectables::fromConsultation($this->consultation));
    }
}
