<?php

namespace app\plugins\openslides;

use app\components\ExternalPasswordAuthenticatorInterface;
use app\models\db\User;
use GuzzleHttp\Exception\GuzzleException;
use app\models\exceptions\{Internal, Login, LoginInvalidUser};

class PasswordAuthenticator implements ExternalPasswordAuthenticatorInterface
{
    public function __construct(
        private SiteSettings $settings,
        private OpenslidesClient $osClient,
        private AutoupdateSyncService $syncService
    ) {
    }

    public function getAuthPrefix(): string
    {
        return $this->settings->getAuthPrefix();
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

    public function replacesLocalUserAccounts(): bool
    {
        return false;
    }

    public function resetPasswordAlternativeLink(): ?string
    {
        return $this->settings->osBaseUri . 'login/reset-password';
    }

    /**
     * @throws Login
     * @throws LoginInvalidUser
     */
    public function performLogin(string $username, string $password): User
    {
        try {
            $loginResponse = $this->osClient->login($username, $password);
        } catch (GuzzleException $e) {
            throw new Login(method_exists($e, 'getMessage') ? $e->getMessage() : get_class($e));
        }
        $osUser = $loginResponse->getUser();

        return $this->syncService->syncUser($osUser);
    }

    public function performRegistration(string $username, string $password): User
    {
        throw new Internal('Please visit Openslides to register');
    }

    public function formatUsername(User $user): string
    {
        return $user->name;
    }
}
