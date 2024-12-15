<?php

declare(strict_types=1);

namespace app\components;

use app\controllers\{MotionController, PagesController, SpeechController, UserController, VotingController};
use app\models\layoutHooks\Layout;
use app\models\db\User;
use Endroid\QrCode\Label\Font\FontInterface;
use app\models\http\{RedirectResponse, ResponseInterface};
use app\models\settings\AntragsgruenApp;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use OTPHP\TOTP;
use yii\web\Session;

class SecondFactorAuthentication
{
    private Session $session;

    private const TYPE_TOTP = 'totp';

    private const SESSION_KEY_2FA_SETUP_KEY = 'settingUp2FAKey';
    private const SESSION_KEY_2FA_ONGOING = 'loggedIn2FAInProgress';
    private const SESSION_KEY_2FA_REGISTRATION_ONGOING = 'loggedInForced2FARegistrationInProgress';
    public const TIMEOUT_2FA_SESSION = 300;

    private const IMPLICITLY_CALLED_URLS = [
        PagesController::class => [PagesController::VIEW_ID_FILES, PagesController::VIEW_ID_CSS],
        SpeechController::class => [SpeechController::VIEW_ID_GET_QUEUE],
        VotingController::class => [VotingController::VIEW_ID_GET_OPEN_VOTING_BLOCKS, VotingController::VIEW_ID_GET_ADMIN_VOTING_BLOCKS],
        MotionController::class => [MotionController::VIEW_ID_MERGING_STATUS_AJAX, MotionController::VIEW_ID_MERGING_PUBLIC_AJAX],
    ];

    public function __construct(Session $session) {
        $this->session = $session;
    }

    public function userHasSecondFactorSetUp(User $user): bool
    {
        $settings = $user->getSettingsObj();

        return $settings->secondFactorKeys !== null && count($settings->secondFactorKeys) > 0;
    }

    public function createSecondFactorKey(User $user): TOTP
    {
        if (YII_ENV === 'test') {
            /** @var non-empty-string $secret */
            $secret = trim((string) file_get_contents(__DIR__ . '/../tests/config/2fa.secret'));
            $otp = TOTP::createFromSecret($secret);
        } else {
            $data = $this->session->get(self::SESSION_KEY_2FA_SETUP_KEY);
            if ($data && $data['user'] === $user->id && $data['time'] > time() - self::TIMEOUT_2FA_SESSION) {
                $otp = TOTP::createFromSecret($data['secret']);
            } else {
                $otp = TOTP::generate();
            }
        }
        $otp->setLabel(AntragsgruenApp::getInstance()->mailFromName ?: 'Antragsgrün');

        $this->session->set(self::SESSION_KEY_2FA_SETUP_KEY, [
            'user' => $user->id,
            'time' => time(),
            'secret' => $otp->getSecret(),
        ]);

        return $otp;
    }

    private function checkOtp(TOTP $totp, string $code): bool
    {
        /** @var int<1, max> $shortInThePast */
        $shortInThePast = time() - 15;

        return $totp->now() === $code || $totp->at($shortInThePast) === $code;
    }

    /**
     * @param non-empty-string $secret
     */
    private function setOtpToUser(User $user, string $secret): void
    {
        $userSettings = $user->getSettingsObj();
        if (!$userSettings->secondFactorKeys) {
            $userSettings->secondFactorKeys = [];
        }
        $userSettings->secondFactorKeys[] = [
            'type' => self::TYPE_TOTP,
            'secret' => $secret,
        ];
        $user->setSettingsObj($userSettings);
    }

    /**
     * @throws \RuntimeException
     */
    public function attemptRegisteringSecondFactor(User $user, string $secondFactor): User
    {
        $data = $this->session->get(self::SESSION_KEY_2FA_SETUP_KEY);
        if (!$data || $data['user'] !== $user->id) {
            throw new \RuntimeException(\Yii::t('user', 'err_2fa_nosession_user'));
        }
        if ($data['time'] < time() - self::TIMEOUT_2FA_SESSION) {
            $minutes = SecondFactorAuthentication::TIMEOUT_2FA_SESSION / 60;
            $msg = str_replace('%minutes%', (string) $minutes, \Yii::t('user', 'err_2fa_timeout'));
            throw new \RuntimeException(str_replace('%seconds%', (string)self::TIMEOUT_2FA_SESSION, $msg));
        }
        if (!$secondFactor) {
            throw new \RuntimeException(\Yii::t('user', 'err_2fa_empty'));
        }

        $otp = TOTP::createFromSecret($data['secret']);
        if (!$this->checkOtp($otp, $secondFactor)) {
            throw new \RuntimeException(\Yii::t('user', 'err_2fa_incorrect'));
        }

        $this->setOtpToUser($user, $data['secret']);

        $this->session->remove(self::SESSION_KEY_2FA_SETUP_KEY);

        return $user;
    }

    public function attemptRemovingSecondFactor(User $user, string $secondFactor): ?string
    {
        $userSettings = $user->getSettingsObj();
        if ($userSettings->secondFactorKeys === null || count($userSettings->secondFactorKeys) === 0) {
            return \Yii::t('user', 'err_2fa_nocode');
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

        return \Yii::t('user', 'err_2fa_incorrect');
    }

    public function onUsernamePwdLoginSuccess(User $user, string $backUrl): ?ResponseInterface
    {
        if ($this->userHasSecondFactorSetUp($user)) {
            return $this->initSecondFactorAuth($user, $backUrl);
        } elseif ($this->isForcedToSetupSecondFactor($user)) {
            return $this->initForcedSecondFactorSetting($user, $backUrl);
        } else {
            return null;
        }
    }

    private function initSecondFactorAuth(User $user, string $backUrl): ResponseInterface
    {
        $this->session->set(self::SESSION_KEY_2FA_ONGOING, [
            'time' => time(),
            'user_id' => $user->id,
        ]);
        return new RedirectResponse(UrlHelper::createUrl(['/user/login2fa', 'backUrl' => $backUrl]));
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

    private function initForcedSecondFactorSetting(User $user, string $backUrl): ResponseInterface
    {
        $this->session->set(self::SESSION_KEY_2FA_REGISTRATION_ONGOING, [
            'time' => time(),
            'user_id' => $user->id,
        ]);
        return new RedirectResponse(UrlHelper::createUrl(['/user/login2fa-force-registration', 'backUrl' => $backUrl]));
    }

    public function getOngoingSessionUser(): ?User
    {
        $data = $this->session->get(self::SESSION_KEY_2FA_ONGOING);
        if (!$data) {
            return null;
        }
        if ($data['time'] < time() - self::TIMEOUT_2FA_SESSION) {
            return null;
        }

        return User::findOne(['id' => $data['user_id']]);
    }

    public function confirmLoginWithSecondFactor(string $secondFactor): ?User
    {
        $user = $this->getOngoingSessionUser();
        if (!$user) {
            return null;
        }

        $userSettings = $user->getSettingsObj();
        if (!$userSettings->secondFactorKeys) {
            return null;
        }
        foreach ($userSettings->secondFactorKeys as $key) {
            $totp = TOTP::createFromSecret($key['secret']);
            if ($this->checkOtp($totp, $secondFactor)) {
                return $user;
            }
        }

        return null;
    }

    public function getForcedRegistrationUser(): User
    {
        $data = $this->session->get(self::SESSION_KEY_2FA_REGISTRATION_ONGOING);
        if (!$data) {
            throw new \RuntimeException(\Yii::t('user', 'err_2fa_nosession'));
        }

        $user = User::findOne(['id' => $data['user_id']]);
        if (!$user) {
            throw new \RuntimeException('Invalid login session ongoing');
        }

        return $user;
    }

    /**
     * @throws \RuntimeException
     */
    public function createForcedRegistrationSecondFactor(): TOTP
    {
        $user = $this->getForcedRegistrationUser();

        return $this->createSecondFactorKey($user);
    }

    /**
     * @throws \RuntimeException
     */
    public function attemptForcedRegisteringSecondFactor(string $secondFactor): User
    {
        $user = $this->getForcedRegistrationUser();

        $data = $this->session->get(self::SESSION_KEY_2FA_SETUP_KEY);
        if (!$data || $data['user'] !== $user->id) {
            throw new \RuntimeException(\Yii::t('user', 'err_2fa_nosession_user'));
        }
        if ($data['time'] < time() - self::TIMEOUT_2FA_SESSION) {
            $minutes = SecondFactorAuthentication::TIMEOUT_2FA_SESSION / 60;
            $msg = str_replace('%minutes%', (string) $minutes, \Yii::t('user', 'err_2fa_timeout'));
            throw new \RuntimeException(str_replace('%seconds%', (string)self::TIMEOUT_2FA_SESSION, $msg));
        }
        if (!$secondFactor) {
            throw new \RuntimeException(\Yii::t('user', 'err_2fa_empty'));
        }

        $otp = TOTP::createFromSecret($data['secret']);
        if (!$this->checkOtp($otp, $secondFactor)) {
            throw new \RuntimeException(\Yii::t('user', 'err_2fa_incorrect'));
        }

        $this->setOtpToUser($user, $data['secret']);

        $this->session->remove(self::SESSION_KEY_2FA_SETUP_KEY);
        $this->session->remove(self::SESSION_KEY_2FA_REGISTRATION_ONGOING);

        return $user;
    }

    private static function getQrCodeLabelFont(): FontInterface
    {
        return new class() implements FontInterface {
            public function getPath(): string {
                return __DIR__ . '/../assets/PT-Sans/PTS55F.ttf';
            }
            public function getSize(): int {
                return 16;
            }
        };
    }

    public static function createQrCode(TOTP $totp): ResultInterface
    {
        $logo = Layout::squareLogoPath();
        if (!$logo) {
            $logo = __DIR__.'/../web/favicons/apple-touch-icon.png';
        }

        $url = $totp->getProvisioningUri();
        return Builder::create()
                         ->writer(new PngWriter())
                         ->writerOptions([])
                         ->data($url)
                         ->encoding(new Encoding('UTF-8'))
                         ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                         ->size(300)
                         ->margin(10)
                         ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                         ->logoPath($logo)
                         ->logoResizeToWidth(50)
                         ->logoResizeToHeight(50)
                         ->logoPunchoutBackground(true)
                         ->labelFont(self::getQrCodeLabelFont())
                         ->labelText('')
                         ->validateResult(false)
                         ->build();
    }

    public function onPageView(string $controller, string $actionId): void
    {
        if (isset(self::IMPLICITLY_CALLED_URLS[$controller]) && in_array($actionId, self::IMPLICITLY_CALLED_URLS[$controller])) {
            // Could be an implicit load of custom CSS or a logo
            return;
        }
        if ($controller !== UserController::class || $actionId !== UserController::VIEW_ID_LOGIN_FORCE_2FA_REGISTRATION) {
            $this->session->remove(self::SESSION_KEY_2FA_REGISTRATION_ONGOING);
        }
        if ($controller !== UserController::class || $actionId !== UserController::VIEW_ID_LOGIN_2FA) {
            $this->session->remove(self::SESSION_KEY_2FA_ONGOING);
        }
    }
}
