<?php

declare(strict_types=1);

namespace app\models\api\user;

use app\components\JwtCreator;
use app\models\db\{Consultation, User};

class UserLoginResponse
{
    public function __construct(
        public string $token,
        public int $exp,
    ) {
    }

    public static function fromLogin(Consultation $consultation, User $user): self
    {
        $jwt = JwtCreator::getJwtConfigForUser($consultation, $user);

        return new self($jwt['token'], $jwt['exp']);
    }
}
