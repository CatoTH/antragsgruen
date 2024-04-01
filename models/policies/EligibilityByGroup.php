<?php

declare(strict_types=1);

namespace app\models\policies;

use app\models\db\{Consultation, ConsultationUserGroup, User};

class EligibilityByGroup implements \JsonSerializable
{
    public int $groupId;
    public string $groupTitle;

    /** @var array<array{user_id: int, user_name: string, weight: int}> */
    public array $users;

    public static function fromUserGroup(ConsultationUserGroup $group, Consultation $consultation): self
    {
        $eligibility = new EligibilityByGroup();
        $eligibility->groupId = $group->id;
        $eligibility->groupTitle = $group->getNormalizedTitle();
        $eligibility->users = array_map(function (User $user) use ($consultation): array {
            return [
                'user_id' => $user->id,
                'user_name' => $user->getAuthUsername(),
                'weight' => $user->getSettingsObj()->getVoteWeight($consultation),
            ];
        }, $group->users);

        return $eligibility;
    }

    public static function fromJsonArray(array $data): self
    {
        $eligibility = new EligibilityByGroup();
        $eligibility->groupId = $data['id'];
        $eligibility->groupTitle = $data['title'];
        $eligibility->users = $data['users'];

        return $eligibility;
    }

    /**
     * @return self[]|null
     */
    public static function listFromJsonArray(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }
        return array_map(function (array $dat): self {
            return self::fromJsonArray($dat);
        }, $data);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->groupId,
            'title' => $this->groupTitle,
            'users' => $this->users,
        ];
    }
}
