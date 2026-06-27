<?php

declare(strict_types=1);

namespace app\models\api\user;

use app\models\db\User;

class UserInfo
{
    public function __construct(
        public string $auth,
    ) {
    }

    public static function fromEntity(User $entity): self
    {
        return new self($entity->auth);
    }
}
