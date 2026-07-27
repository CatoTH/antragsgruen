<?php

declare(strict_types=1);

namespace app\models\api;

use app\models\db\{SpeechQueueItem, SpeechSubqueue};

class SpeechQueueActiveSlot
{
    public int $id;
    public ?int $subqueueId;
    public string $subqueueName;
    public string $name;
    public ?int $userId;
    public ?string $userToken;
    public int $position;

    public ?\DateTime $dateStarted;
    public ?\DateTime $dateStopped;
    public ?\DateTime $dateApplied;

    public static function fromEntity(SpeechQueueItem $entity, ?SpeechSubqueue $subqueue): self
    {
        $dto = new self();
        $dto->id = $entity->id;
        $dto->subqueueId = $subqueue?->id;
        $dto->subqueueName = ($subqueue ? $subqueue->name : 'default');
        $dto->name = $entity->name;
        $dto->userId = $entity->userId;
        $dto->userToken = $entity->userToken;
        $dto->position = $entity->position;
        $dto->dateStarted = $entity->getDateStarted();
        $dto->dateStopped = $entity->getDateStopped();
        $dto->dateApplied = $entity->getDateApplied();

        return $dto;
    }

}
