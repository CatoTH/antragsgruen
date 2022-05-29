<?php

declare(strict_types=1);

namespace app\models\policies;

use app\models\db\{ConsultationUserGroup, User};

class EligibilityByGroup implements \JsonSerializable
{
    /** @var int */
    public $groupId;

    /** @var string */
    public $groupTitle;

    /** @var array - keys: user_id, user_name */
    public $users;

    public static function fromUserGroup(ConsultationUserGroup $group): self
    {
        $eligibility = new EligibilityByGroup();
        $eligibility->groupId = $group->id;
        $eligibility->groupTitle = $group->getNormalizedTitle();
        $eligibility->users = array_map(function (User $user): array {
            return [
                'user_id' => $user->id,
                'user_name' => $user->getAuthUsername(),
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

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->groupId,
            'title' => $this->groupTitle,
            'users' => $this->users,
        ];
    }
}
