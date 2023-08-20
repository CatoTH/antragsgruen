<?php

declare(strict_types=1);

namespace app\models\api;

use app\components\CookieUser;
use app\models\db\User;

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

    public function toUserApi(bool $showNames, ?User $user, ?CookieUser $cookieUser): array
    {
        /** @var SpeechSubqueueItem[] $applied */
        $applied = array_values(array_filter($this->items, fn(SpeechSubqueueItem $item): bool => $item->isApplication()));

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'num_applied'  => count($applied),
            'have_applied' => false, // true if a user (matching userID or userToken) is on the list, but has not spoken yet (including assigned places)
        ];

        foreach ($applied as $item) {
            if (!$item->dateStarted && $item->isMe($user, $cookieUser)) {
                $data['have_applied'] = true;
            }
        }

        if ($showNames) {
            $data['applied'] = array_map(fn (SpeechSubqueueItem $item) => $item->toUserApi(), $applied);
        }

        return $data;
    }

    public function toAdminApi(): array
    {
        $applied = array_values(array_filter($this->items, fn(SpeechSubqueueItem $item): bool => $item->isApplication()));
        $onList = array_values(array_filter($this->items, fn(SpeechSubqueueItem $item): bool => $item->isOnList()));
        return [
            'id' => $this->id,
            'name' => $this->name,
            'onlist' => array_map(fn (SpeechSubqueueItem $item) => $item->toAdminApi(), $onList),
            'applied' => array_map(fn (SpeechSubqueueItem $item) => $item->toAdminApi(), $applied),
        ];
    }
}
