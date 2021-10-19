<?php

namespace app\components;

use app\models\settings\AntragsgruenApp;
use Gregwar\Captcha\CaptchaBuilder;

class Captcha
{
    public static function needsCaptcha()
    {
        return AntragsgruenApp::getInstance()->loginCaptcha;
    }

    public static function createInlineCaptcha()
    {
        $builder = new CaptchaBuilder();
        $builder->build(300, 80);

        $phrase = $builder->getPhrase();
        \Yii::$app->session->set('captcha', $phrase);

        return $builder->inline();
    }

    public static function checkEnteredCaptcha($captcha)
    {
        $savedCaptcha = \Yii::$app->session->get('captcha');
        \Yii::$app->session->set('captcha', null);
        if (!$captcha || !$savedCaptcha || mb_strlen($savedCaptcha) < 5) {
            return false;
        }
        return mb_strtolower($savedCaptcha) === mb_strtolower($captcha);
    }
}
