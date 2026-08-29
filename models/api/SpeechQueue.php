<?php

declare(strict_types=1);

namespace app\models\api;

use app\models\settings\SpeechQueue as SpeechQueueSettings;
use Symfony\Component\Serializer\Attribute\Ignore;

class SpeechQueue
{
    public int $id;
    public bool $isActive;
    public SpeechQueueSettings $settings;
    /** @var SpeechSubqueue[] */
    public array $subqueues;
    /** @var SpeechQueueActiveSlot[] */
    public array $slots;
    public bool $requiresLogin;
    public ?LocalizedString $otherActiveName;
    public int $currentTime;

    /**
     * @return SpeechQueueActiveSlot[]
     */
    #[Ignore]
    private static function getActiveSlots(\app\models\db\SpeechQueue $entity): array
    {
        $slots = [];
        foreach ($entity->items as $item) {
            if ($item->position === null || $item->position < 0) {
                continue;
            }
            $subqueue = ($item->subqueueId ? $entity->getSubqueueById($item->subqueueId) : null);
            $slots[] = SpeechQueueActiveSlot::fromEntity($entity->getMyConsultation(), $item, $subqueue);
        }
        usort($slots, function (SpeechQueueActiveSlot $entry1, SpeechQueueActiveSlot $entry2) {
            return $entry1->position <=> $entry2->position;
        });
        return $slots;
    }

    public static function fromEntity(\app\models\db\SpeechQueue $entity): self
    {
        $dto = new self();
        $dto->id = $entity->id;
        $dto->isActive = !!$entity->isActive;
        $dto->settings = $entity->getSettings();
        $dto->subqueues = self::getSubqueues($entity);
        $dto->slots = self::getActiveSlots($entity);
        $dto->requiresLogin = $entity->getMyConsultation()->getSettings()->speechRequiresLogin;
        $dto->currentTime = (int)round(microtime(true) * 1000); // needs to include milliseconds for accuracy

        $dto->otherActiveName = null;
        foreach ($entity->getMyConsultation()->speechQueues as $otherQueue) {
            if ($entity->agendaItemId !== null) {
                continue; // There can be multiple active queues for agenda items, so no need to detect them
            }
            if ($otherQueue->isActive && $otherQueue->id !== $entity->id && $otherQueue->agendaItemId === null) {
                // Localized, as this payload is also pushed to all readers at once, in every language
                $dto->otherActiveName = LocalizedString::build(
                    $entity->getMyConsultation(),
                    fn () => $otherQueue->getTitle()
                );
            }
        }

        return $dto;
    }

    /**
     * @return SpeechSubqueue[]
     */
    #[Ignore]
    private static function getSubqueues(\app\models\db\SpeechQueue $entity): array
    {
        $subqueues = [];
        foreach ($entity->subqueues as $subqueue) {
            $subqueues[] = SpeechSubqueue::fromEntity($subqueue, $entity);
        }

        // Users without subqueue when there actually are existing subqueues:
        // this happens if a queue starts off without subqueues, someone registers,
        // and only afterward subqueues are created. In this case, there will be a placeholder "default" queue.
        $usersWithoutSubqueue = 0;
        foreach ($entity->items as $item) {
            if ($item->subqueueId === null && $item->position < 0) {
                $usersWithoutSubqueue++;
            }
        }
        if (count($subqueues) === 0 || $usersWithoutSubqueue > 0) {
            $subqueues[] = SpeechSubqueue::fromEntity(null, $entity);
        }

        return $subqueues;
    }

}
