<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\FailedLoginAttempt;
use app\models\settings\AntragsgruenApp;
use Gregwar\Captcha\CaptchaBuilder;

class Captcha
{
    public static function needsCaptcha(?string $username): bool
    {
        return false;
        
        if (FailedLoginAttempt::needsLoginThrottling($username)) {
            return true;
        }
        return AntragsgruenApp::getInstance()->loginCaptcha;
    }

    public static function createInlineCaptcha(): string
    {
        $builder = new CaptchaBuilder();
        $builder->build(300, 80);

        $phrase = $builder->getPhrase();
        RequestContext::getSession()->set('captcha', $phrase);

        return $builder->inline();
    }

    public static function checkEnteredCaptcha(?string $captcha): bool
    {
        $savedCaptcha = RequestContext::getSession()->get('captcha');
        RequestContext::getSession()->set('captcha', null);
        if (!$captcha || !$savedCaptcha || mb_strlen($savedCaptcha) < 5) {
            return false;
        }
        return mb_strtolower($savedCaptcha) === mb_strtolower($captcha);
    }
}
