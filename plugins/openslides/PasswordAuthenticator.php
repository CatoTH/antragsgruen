<?php

namespace app\plugins\openslides;

use app\components\ExternalPasswordAuthenticatorInterface;
use app\models\db\User;
use app\models\exceptions\Login;
use app\models\exceptions\LoginInvalidPassword;
use app\models\exceptions\LoginInvalidUser;

class PasswordAuthenticator implements ExternalPasswordAuthenticatorInterface
{
    /** @var SiteSettings */
    private $settings;

    public function __construct(SiteSettings $settings)
    {
        $this->settings = $settings;
    }

    public function getAuthPrefix(): string
    {
        // TODO: Implement getAuthPrefix() method.
    }

    public function supportsCreatingAccounts(): bool
    {
        return false;
    }

    public function supportsChangingPassword(): bool
    {
        return false;
    }

    public function supportsResetPassword(): bool
    {
        return false;
    }

    public function resetPasswordAlternativeLink(): ?string
    {
        return $this->settings->osBaseUri . 'login/reset-password';
    }

    public function performLogin(string $username, string $password): User
    {
        // TODO: Implement performLogin() method.
    }

    public function performRegistration(string $username, string $password): User
    {
        // TODO: Implement performRegistration() method.
    }

    public function formatUsername(User $user): string
    {
        // TODO: Implement formatUsername() method.
    }
}
