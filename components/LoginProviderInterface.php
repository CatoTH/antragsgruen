<?php

namespace app\components;

use app\models\db\User;
use app\models\exceptions\{Login, LoginInvalidPassword, LoginInvalidUser};

interface LoginProviderInterface
{
    public function getId(): string;
    public function getName(): string;
    public function renderLoginForm(string $backUrl, bool $active): string;
    public function performLoginAndReturnUser(): User;
    public function userWasLoggedInWithProvider(?User $user): bool;

    /**
     * @throws \Exception
     */
    public function logoutCurrentUserIfRelevant(string $backUrl): ?string;

    public function renderAddMultipleUsersForm(): ?string;
}
