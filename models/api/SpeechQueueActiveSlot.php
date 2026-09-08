<?php

declare(strict_types=1);

namespace app\models\api;

use app\models\api\SpeechSubqueue as SpeechSubqueueApi;
use app\models\db\{Consultation, SpeechQueueItem, SpeechSubqueue};

class SpeechQueueActiveSlot
{
    public int $id;
    public ?int $subqueueId;
    /** Localized for the same reason as SpeechSubqueue::$name */
    public LocalizedString $subqueueName;
    public string $name;
    public ?int $userId;
    public ?string $userToken;
    public int $position;

    public ?\DateTime $dateStarted;
    public ?\DateTime $dateStopped;
    public ?\DateTime $dateApplied;

    public static function fromEntity(Consultation $consultation, SpeechQueueItem $entity, ?SpeechSubqueue $subqueue): self
    {
        $dto = new self();
        $dto->id = $entity->id;
        $dto->subqueueId = $subqueue?->id;
        $dto->subqueueName = LocalizedString::fromString(
            $consultation,
            $subqueue ? $subqueue->name : SpeechSubqueueApi::AUTO_QUEUE_NAME
        );
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
