<?php

namespace app\components;

use app\models\db\User;
use app\models\exceptions\{Login, LoginInvalidPassword, LoginInvalidUser};

interface LoginProviderInterface
{
    public function getId(): string;
    public function getName(): string;
    public function renderLoginForm(string $backUrl): string;
}
