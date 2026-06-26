<?php

declare(strict_types=1);

namespace app\models\api\user;

use app\models\db\User;

class UserInfo
{
    public string $userAuth;

    public static function fromEntity(User $entity): self
    {
        $self = new self();
        $self->userAuth = $entity->auth;

        return $self;
    }
}
