<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Site, User};
use app\models\http\{RedirectResponse, ResponseInterface};
use app\models\settings\AntragsgruenApp;
use OTPHP\TOTP;
use yii\web\Session;

class SecondFactorAuthentication
{
    private ?Site $site;
    private Session $session;

    private const TYPE_TOTP = 'totp';

    private const SESSION_KEY_2FA_SETUP_KEY = 'settingUp2FAKey';
    private const SESSION_KEY_2FA_ONGOING = 'loggedInWithMissing2FA';
    private const TIMEOUT_2FA_SESSION = 300;

    public function __construct(?Site $site, Session $session) {
        $this->site = $site;
        $this->session = $session;
    }

    public function userHasSecondFactorSetUp(User $user): bool
    {
        $settings = $user->getSettingsObj();

        return $settings->secondFactorKeys && count($settings->secondFactorKeys) > 0;
    }

    public function createSecondFactorKey(User $user): TOTP
    {
        $otp = TOTP::generate();
        $otp->setLabel(AntragsgruenApp::getInstance()->mailFromName);

        $this->session->set(self::SESSION_KEY_2FA_SETUP_KEY, [
            'user' => $user->id,
            'time' => time(),
            'secret' => $otp->getSecret(),
        ]);

        return $otp;
    }

    private function checkOtp(TOTP $totp, string $code): bool
    {
        return $totp->now() === $code || $totp->at(time() - 15) === $code;
    }

    public function attemptRegisteringSecondFactor(User $user, string $secondFactor): ?string
    {
        $data = $this->session->get(self::SESSION_KEY_2FA_SETUP_KEY);
        if (!$data || $data['user'] !== $user->id) {
            return 'No ongoing TOTP registration for the current user found';
        }
        if ($data['time'] < time() - self::TIMEOUT_2FA_SESSION) {
            return str_replace('%seconds%', (string)self::TIMEOUT_2FA_SESSION, 'Please confirm the second factor within %seconds% seconds.');
        }

        $otp = TOTP::createFromSecret($data['secret']);
        if (!$this->checkOtp($otp, $secondFactor)) {
            return 'Incorrect code provided';
        }

        $userSettings = $user->getSettingsObj();
        if (!$userSettings->secondFactorKeys) {
            $userSettings->secondFactorKeys = [];
        }
        $userSettings->secondFactorKeys[] = [
            'type' => self::TYPE_TOTP,
            'secret' => $data['secret'],
        ];
        $user->setSettingsObj($userSettings);

        $this->session->remove(self::SESSION_KEY_2FA_SETUP_KEY);

        return null;
    }

    public function attemptRemovingSecondFactor(User $user, string $secondFactor): ?string
    {
        $userSettings = $user->getSettingsObj();
        if (!$userSettings->secondFactorKeys || count($userSettings->secondFactorKeys) === 0) {
            return 'No second factor registered';
        }

        foreach ($userSettings->secondFactorKeys as $index => $key) {
            $totp = TOTP::createFromSecret($key['secret']);
            if ($this->checkOtp($totp, $secondFactor)) {
                $keys = $userSettings->secondFactorKeys;
                unset ($keys[$index]);
                $userSettings->secondFactorKeys = array_values($keys);
                $user->setSettingsObj($userSettings);

                return null;
            }
        }

        return 'Invalid code entered';
    }

    public function onUsernamePwdLoginSuccess(User $user): ?ResponseInterface
    {
        if ($this->userHasSecondFactorSetUp($user)) {
            return $this->initSecondFactorAuth($user);
        } elseif ($this->isForcedToSetupSecondFactor($user)) {
            return $this->initForcedSecondFactorSetting($user);
        } else {
            return null;
        }
    }

    private function initSecondFactorAuth(User $user): ResponseInterface
    {
        $this->session->set(self::SESSION_KEY_2FA_ONGOING, [
            'time' => time(),
            'user_id' => $user->id,
        ]);
        return new RedirectResponse(UrlHelper::createUrl('/user/login2fa'));
    }

    public function isForcedToSetupSecondFactor(User $user): bool
    {
        if (AntragsgruenApp::getInstance()->enforceTwoFactorAuthentication) {
            return true;
        }
        if ($user->getSettingsObj()->enforceTwoFactorAuthentication) {
            return true;
        }

        return false;
    }

    private function initForcedSecondFactorSetting(User $user): ResponseInterface
    {

    }

    public function hasOngoingSession(): bool
    {
        $data = $this->session->get(self::SESSION_KEY_2FA_ONGOING);
        if (!$data) {
            return false;
        }
        if ($data['time'] < time() - self::TIMEOUT_2FA_SESSION) {
            return false;
        }
        return true;
    }

    public function confirmLoginWithSecondFactor(string $secondFactor): ?User
    {
        if (!$this->hasOngoingSession()) {
            return null;
        }
        $data = $this->session->get(self::SESSION_KEY_2FA_ONGOING);
        $user = User::findOne(['id' => $data['user_id']]);
        if (!$user) {
            return null;
        }

        $userSettings = $user->getSettingsObj();
        if (!$userSettings->secondFactorKeys) {
            return null;
        }
        foreach ($userSettings->secondFactorKeys as $index => $key) {
            $totp = TOTP::createFromSecret($key['secret']);
            if ($this->checkOtp($totp, $secondFactor)) {
                return $user;
            }
        }

        return null;
    }
}
