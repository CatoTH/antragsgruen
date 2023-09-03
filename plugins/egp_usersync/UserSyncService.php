<?php

declare(strict_types=1);

namespace app\plugins\egp_usersync;

use app\plugins\egp_usersync\DTO\UserList;

class UserSyncService
{
    /**
     * @param UserList[] $userLists
     *
     * @return array{added: integer, removed: integer, unchanged: integer}
     */
    public function syncLists(array $userLists): array
    {
        $added = 0;
        $removed = 0;
        $unchanged = 0;

        foreach ($userLists as $userList) {
            foreach ($userList->getUsers() as $user) {
                $unchanged++; // @TODO
            }
        }

        return [
            'added' => $added,
            'removed' => $removed,
            'unchanged' => $unchanged,
        ];
    }
}
