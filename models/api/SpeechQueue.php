<?php

declare(strict_types=1);

namespace app\models\api;

use app\components\CookieUser;
use app\models\db\User;
use app\models\settings\SpeechQueue as SpeechQueueSettings;
use Symfony\Component\Serializer\Annotation\Ignore;

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
    public ?string $otherActiveName;
    public int $currentTime;

    /**
     * @return SpeechQueueActiveSlot[]
     * @Ignore
     */
    private static function getActiveSlots(\app\models\db\SpeechQueue $entity): array
    {
        $slots = [];
        foreach ($entity->items as $item) {
            if ($item->position === null || $item->position < 0) {
                continue;
            }
            $subqueue = ($item->subqueueId ? $entity->getSubqueueById($item->subqueueId) : null);
            $slots[] = SpeechQueueActiveSlot::fromEntity($item, $subqueue);
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
            if ($otherQueue->isActive && $otherQueue->id !== $entity->id) {
                $dto->otherActiveName = $otherQueue->getTitle();
            }
        }

        return $dto;
    }

    /**
     * @return SpeechSubqueue[]
     * @Ignore()
     */
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

    public function toUserApi(?User $user, ?CookieUser $cookieUser): array
    {
        $subqueues = array_map(fn(SpeechSubqueue $subqueue) => $subqueue->toUserApi($this->settings->showNames, $user, $cookieUser), $this->subqueues);

        $haveApplied = false;
        foreach ($subqueues as $subqueue) {
            if ($subqueue['have_applied']) {
                $haveApplied = true;
            }
        }

        return [
            'id' => $this->id,
            'is_open' => $this->settings->isOpen,
            'have_applied' => $haveApplied,
            'allow_custom_names' => $this->settings->allowCustomNames,
            'is_open_poo' => $this->settings->isOpenPoo,
            'subqueues' => $subqueues,
            'slots' => array_map(fn(SpeechQueueActiveSlot $slot) => $slot->toApi(), $this->slots),
            'requires_login' => $this->requiresLogin,
            'current_time' => $this->currentTime,
            'speaking_time' => $this->settings->speakingTime,
        ];
    }

    /**
     * @Ignore()
     */
    public function getAdminApiObject(): array
    {
        return [
            'id'                => $this->id,
            'is_active'         => $this->isActive,
            'settings'          => $this->settings->getAdminApiObject(),
            'subqueues'         => array_map(fn(SpeechSubqueue $subqueue) => $subqueue->toAdminApi(), $this->subqueues),
            'slots'             => array_map(fn(SpeechQueueActiveSlot $slot) => $slot->toApi(), $this->slots),
            'other_active_name' => $this->otherActiveName,
            'current_time' => $this->currentTime,
        ];
    }
}
