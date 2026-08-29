<?php

declare(strict_types=1);

namespace app\models\api;

class SpeechSubqueue
{
    public const AUTO_QUEUE_ID = -1;
    public const AUTO_QUEUE_NAME = 'default';

    public int $id;
    /**
     * Localized, as this payload is also pushed to all readers of the consultation at once. The name
     * is a single string in the database so far, so it currently resolves to the same text in every
     * language - the API and the Live server handle a per-language name either way.
     */
    public LocalizedString $name;
    /** @var SpeechSubqueueItem[] */
    public array $items;

    public static function fromEntity(?\app\models\db\SpeechSubqueue $entity, \app\models\db\SpeechQueue $queueEntity): self
    {
        $consultation = $queueEntity->getMyConsultation();

        $dto = new self();
        if ($entity) {
            $dto->id = $entity->id;
            $dto->name = LocalizedString::fromString($consultation, $entity->name);
        } else {
            $dto->id = self::AUTO_QUEUE_ID;
            // A placeholder the frontend replaces by a translated label of its own
            $dto->name = LocalizedString::fromString($consultation, self::AUTO_QUEUE_NAME);
        }

        $dto->items = [];
        foreach ($queueEntity->getSortedItems($entity) as $item) {
            $dto->items[] = SpeechSubqueueItem::fromEntity($item);
        }

        return $dto;
    }

}
