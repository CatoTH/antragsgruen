<?php

declare(strict_types=1);

namespace app\models\api;

class SpeechSubqueue
{
    public const AUTO_QUEUE_ID = -1;
    public const AUTO_QUEUE_NAME = 'default';

    public int $id;
    public string $name;
    /** @var SpeechSubqueueItem[] */
    public array $items;

    public static function fromEntity(?\app\models\db\SpeechSubqueue $entity, \app\models\db\SpeechQueue $queueEntity): self
    {
        $dto = new self();
        if ($entity) {
            $dto->id = $entity->id;
            $dto->name = $entity->name;
        } else {
            $dto->id = self::AUTO_QUEUE_ID;
            $dto->name = self::AUTO_QUEUE_NAME;
        }

        $dto->items = [];
        foreach ($queueEntity->getSortedItems($entity) as $item) {
            $dto->items[] = SpeechSubqueueItem::fromEntity($item);
        }

        return $dto;
    }

}
