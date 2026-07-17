<?php

declare(strict_types=1);

namespace app\models\api\user;

class UserLoginRequest
{
    public function __construct(
        public string $username,
        public string $password,
    ) {
    }
}
