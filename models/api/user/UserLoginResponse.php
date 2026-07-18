<?php

declare(strict_types=1);

namespace app\models\api\user;

use app\components\JwtCreator;
use app\models\db\{Site, User};

class UserLoginResponse
{
    public function __construct(
        public string $token,
        public int $exp,
    ) {
    }

    public static function fromLogin(Site $site, User $user): self
    {
        $jwt = JwtCreator::getJwtConfigForUser($site, $user);

        return new self($jwt['token'], $jwt['exp']);
    }
}
