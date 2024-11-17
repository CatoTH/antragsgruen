<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\FailedLoginAttempt;
use app\models\settings\AntragsgruenApp;
use SimpleCaptcha\Builder as CaptchaBuilder;

class Captcha
{
    public static function needsCaptcha(?string $username): bool
    {
        if (FailedLoginAttempt::needsLoginThrottling($username)) {
            return true;
        }
        return AntragsgruenApp::getInstance()->loginCaptcha;
    }

    public static function createInlineCaptcha(): string
    {
        $builder = new CaptchaBuilder();
        $builder->distort = true;
        $builder->bgColor = '#fff';
        @$builder->build(300, 80); // https://github.com/CatoTH/antragsgruen/issues/980

        $phrase = $builder->phrase;
        RequestContext::getSession()->set('captcha', $phrase);

        return $builder->inline();
    }

    public static function checkEnteredCaptcha(?string $captcha): bool
    {
        $savedCaptcha = RequestContext::getSession()->get('captcha');
        RequestContext::getSession()->set('captcha', null);
        if (!$captcha || !$savedCaptcha || grapheme_strlen($savedCaptcha) < 5) {
            return false;
        }
        return mb_strtolower($savedCaptcha) === mb_strtolower($captcha);
    }
}
