<?php

declare(strict_types=1);

namespace app\models\api;

use app\components\CookieUser;
use app\models\db\{SpeechQueueItem, User};

class SpeechSubqueueItem
{
    public int $id;
    public string $name;
    public ?int $userId;
    public ?string $userToken;
    public bool $isPointOfOrder;
    public \DateTime $dateApplied;
    public ?\DateTime $dateStarted;
    public ?int $position;

    public static function fromEntity(SpeechQueueItem $entity): self
    {
        $dto = new self();
        $dto->id = $entity->id;
        $dto->name = $entity->getLocalizedName();
        $dto->userId = $entity->userId;
        $dto->userToken = $entity->userToken;
        $dto->isPointOfOrder = $entity->isPointOfOrder();
        $dto->dateApplied = $entity->getDateApplied() ?? new \DateTime();
        $dto->dateStarted = $entity->getDateStarted();
        $dto->position = $entity->position;

        return $dto;
    }

    public function isMe(?User $user, ?CookieUser $cookieUser): bool
    {
        if ($user && $this->userId && $user->id === $this->userId) {
            return true;
        }
        if ($cookieUser && $cookieUser->userToken === $this->userToken) {
            return true;
        }

        return false;
    }

    public function isOnList(): bool
    {
        return $this->position > 0;
    }

    public function isApplication(): bool
    {
        return $this->position < 0;
    }

    public function toUserApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_point_of_order' => $this->isPointOfOrder,
            'applied_at' => $this->dateApplied->format('c'),
        ];
    }

    public function toAdminApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'user_id' => $this->userId,
            'user_token' => $this->userToken,
            'is_point_of_order' => $this->isPointOfOrder,
            'applied_at' => $this->dateApplied->format('c'),
        ];
    }
}
