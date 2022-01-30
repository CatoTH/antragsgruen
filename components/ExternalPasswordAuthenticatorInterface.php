<?php

namespace app\components;

use app\models\db\User;
use app\models\exceptions\{Login, LoginInvalidPassword, LoginInvalidUser};

interface ExternalPasswordAuthenticatorInterface
{
    public function getAuthPrefix(): string;

    public function supportsCreatingAccounts(): bool; // Only false supported yet
    public function supportsChangingPassword(): bool; // Only false supported yet
    public function supportsResetPassword(): bool; // Only false supported yet
    public function replacesLocalUserAccounts(): bool;
    public function resetPasswordAlternativeLink(): ?string;

    /**
     * @param string $username
     * @param string $password
     *
     * @return User
     * @throws Login
     * @throws LoginInvalidPassword
     * @throws LoginInvalidUser
     */
    public function performLogin(string $username, string $password): User;

    /**
     * @param string $username
     * @param string $password
     *
     * @return User
     */
    public function performRegistration(string $username, string $password): User;

    public function formatUsername(User $user): string;
}
