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
        return match (AntragsgruenApp::getInstance()->captcha['mode']) {
            AntragsgruenApp::CAPTCHA_MODE_ALWAYS => true,
            AntragsgruenApp::CAPTCHA_MODE_NEVER => false,
            default => FailedLoginAttempt::needsLoginThrottling($username),
        };
    }

    public static function createInlineCaptcha(): string
    {
        $builder = new CaptchaBuilder();
        $builder->distort = true;
        $builder->bgColor = '#fff';
        if (AntragsgruenApp::getInstance()->captcha['difficulty'] === AntragsgruenApp::CAPTCHA_DIFFICULTY_EASY) {
            $builder->applyEffects = false;
        }
        $builder->build(300, 80);

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
