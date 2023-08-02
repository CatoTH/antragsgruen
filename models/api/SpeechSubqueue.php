<?php

declare(strict_types=1);

namespace app\models\api;

use app\components\CookieUser;
use app\models\db\User;

class SpeechSubqueue
{
    public ?int $id;
    public ?string $name;
    /** @var SpeechSubqueueItem[] */
    public array $items;

    public static function fromEntity(?\app\models\db\SpeechSubqueue $entity, \app\models\db\SpeechQueue $queueEntity): self
    {
        $dto = new self();
        $dto->id = $entity?->id;
        $dto->name = ($entity ? $entity->name : 'default');

        $dto->items = [];
        foreach ($queueEntity->getAppliedItems($entity) as $item) {
            $dto->items[] = SpeechSubqueueItem::fromEntity($item);
        }

        return $dto;
    }

    public function toUserApi(bool $showNames, ?User $user, ?CookieUser $cookieUser): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'num_applied'  => count($this->items),
            'have_applied' => false, // true if a user (matching userID or userToken) is on the list, but has not spoken yet (including assigned places)
        ];

        foreach ($this->items as $item) {
            if (!$item->dateStarted && $item->isMe($user, $cookieUser)) {
                $data['have_applied'] = true;
            }
        }

        if ($showNames) {
            $data['applied'] = array_map(fn (SpeechSubqueueItem $item) => $item->toUserApi(), $this->items);
        }

        return $data;
    }
}
