<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\components\{DebateTools, Tools, UrlHelper};
use app\models\api\speech\SpeechQueueAdmin;
use app\models\api\debate\{DebateItemTargetType, DebateSelectables, DebateStartRequest, DebateState,
    DebateVotingAssignRequest, DebateVotingBlock, DebateVotingBlockOption, DebateVotingCreateRequest,
    DebateVotingState, DebateVotingStateCreateMode};
use app\models\db\{Amendment, ConsultationAgendaItem, DebateItem, Motion, User, VotingBlock};
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

    /**
     * Get-or-create the speech queue of the currently debated motion or agenda item and return its
     * admin representation, so the "Speaking List" tab can embed the regular speech-admin widget.
     */
    public function actionSpeechQueue(): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);

        if (!$this->consultation || !$this->consultation->getSettings()->hasCurrentlyDebated) {
            return $this->returnRestResponseFromException(
                new NotFound('The "Currently debated" feature is not enabled for this consultation', 404)
            );
        }

        if ($error = $this->getModerationPermissionError()) {
            return $error;
        }

        $debate = DebateItem::getCurrentForConsultation($this->consultation);
        if ($debate === null) {
            return $this->returnRestResponseFromException(new NotFound('No debate is going on right now', 404));
        }
        if ($debate->motionId === null && $debate->agendaItemId === null) {
            return new RestApiExceptionResponse(400, 'A speech queue can only be attached to a debated motion or agenda item');
        }

        $queue = DebateTools::getOrCreateSpeechQueue($debate);

        return $this->createResponse(200, SpeechQueueAdmin::fromEntity($queue));
    }

    /**
     * Get / assign / create / unassign the voting attached to the currently debated item.
     * GET returns the voting state, PUT assigns an existing block, POST creates a new one, DELETE unassigns.
     */
    public function actionVoting(): RestApiResponse
    {
        $this->handleRestHeaders(['GET', 'PUT', 'DELETE', 'POST'], true);

        if (!$this->consultation || !$this->consultation->getSettings()->hasCurrentlyDebated) {
            return $this->returnRestResponseFromException(
                new NotFound('The "Currently debated" feature is not enabled for this consultation', 404)
            );
        }

        if ($error = $this->getModerationPermissionError()) {
            return $error;
        }

        $debate = DebateItem::getCurrentForConsultation($this->consultation);
        $method = $this->getHttpMethod();

        if ($method === 'PUT') {
            if ($debate === null) {
                return $this->returnRestResponseFromException(new NotFound('No debate is going on right now', 404));
            }
            try {
                /** @var DebateVotingAssignRequest $request */
                $request = Tools::getSerializer()->deserialize($this->getPostBody(), DebateVotingAssignRequest::class, 'json');
            } catch (SerializerException $e) {
                return new RestApiExceptionResponse(400, 'Invalid request body: ' . $e->getMessage());
            }
            $votingBlock = $this->consultation->getVotingBlock($request->votingBlockId);
            if ($votingBlock === null) {
                return $this->returnRestResponseFromException(new NotFound('The voting block was not found', 404));
            }
            DebateTools::assignVotingBlock($debate, $votingBlock);
        } elseif ($method === 'DELETE') {
            if ($debate === null) {
                return $this->returnRestResponseFromException(new NotFound('No debate is going on right now', 404));
            }
            DebateTools::unassignVotingBlock($debate);
        } elseif ($method === 'POST') {
            if ($debate === null) {
                return $this->returnRestResponseFromException(new NotFound('No debate is going on right now', 404));
            }
            try {
                /** @var DebateVotingCreateRequest $request */
                $request = Tools::getSerializer()->deserialize($this->getPostBody(), DebateVotingCreateRequest::class, 'json');
            } catch (SerializerException $e) {
                return new RestApiExceptionResponse(400, 'Invalid request body: ' . $e->getMessage());
            }
            DebateTools::createVotingForDebate($debate, $request->question);
        }

        // A mutation may have changed the assignment; reload so the returned state is current.
        $debate = DebateItem::getCurrentForConsultation($this->consultation);

        return $this->createResponse(200, $this->buildVotingState($debate));
    }

    private function buildVotingState(?DebateItem $debate): DebateVotingState
    {
        $adminLink = UrlHelper::createUrl(['/consultation/admin-votings']);

        $selectable = array_map(
            fn (VotingBlock $block) => DebateVotingBlockOption::fromEntity($block),
            $this->consultation->votingBlocks
        );

        if ($debate === null) {
            return new DebateVotingState(
                createMode: DebateVotingStateCreateMode::NONE,
                selectableVotingBlocks: $selectable,
                assignedVotingBlockId: null,
                resolvedVotingBlock: null,
            );
        }

        $target = $debate->getDebateTarget();
        $createMode = match (true) {
            $target instanceof Motion => DebateVotingStateCreateMode::MOTION,
            $target instanceof Amendment => DebateVotingStateCreateMode::AMENDMENT,
            default => DebateVotingStateCreateMode::QUESTION,
        };

        $resolved = DebateTools::getVotingBlockForDebate($debate);

        return new DebateVotingState(
            createMode: $createMode,
            selectableVotingBlocks: $selectable,
            assignedVotingBlockId: $debate->votingBlockId,
            resolvedVotingBlock: ($resolved ? DebateVotingBlock::fromEntity($resolved, $adminLink) : null),
        );
    }
}
