<?php

namespace app\plugins\openslides;

use app\components\ExternalPasswordAuthenticatorInterface;
use app\models\db\User;
use GuzzleHttp\Exception\GuzzleException;
use app\models\exceptions\{Internal, Login, LoginInvalidUser};

class PasswordAuthenticator implements ExternalPasswordAuthenticatorInterface
{
    /** @var SiteSettings */
    private $settings;

    /** @var OpenslidesClient $osClient */
    private $osClient;

    public function __construct(SiteSettings $settings, OpenslidesClient $osClient)
    {
        $this->settings = $settings;
        $this->osClient = $osClient;
    }

    public function getAuthPrefix(): string
    {
        $url = parse_url($this->settings->osBaseUri);
        if (is_array($url) && isset($url['host'])) {
            return 'openslides-' . $url['host'];
        } else {
            throw new Internal('Could not parse osBaseUri');
        }
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


        $auth    = $this->getAuthPrefix() . ':' . $osUser->getId();
        /** @var User|null $userObj */
        $userObj = User::find()->where(['auth' => $auth])->andWhere('status != ' . User::STATUS_DELETED)->one();
        if (!$userObj) {
            $userObj                  = new User();
            $userObj->auth            = $auth;
            $userObj->emailConfirmed  = 1;
            $userObj->pwdEnc          = '';
            $userObj->organizationIds = '';
            $userObj->status          = User::STATUS_CONFIRMED;
        }

        // Set this with every login
        $userObj->name         = $osUser->getUsername();
        $userObj->nameFamily   = $osUser->getLastName();
        $userObj->nameGiven    = $osUser->getFirstName();
        $userObj->organization = '';
        $userObj->email        = $osUser->getEmail();
        $userObj->fixedData    = 1;
        if (!$userObj->save()) {
            var_dump($userObj->getErrors());
            throw new Login('Could not create the user');
        }

        return $userObj;
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
