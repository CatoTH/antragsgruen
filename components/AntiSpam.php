<?php

namespace app\components;

use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

class AntiSpam
{
    public static function createToken(string $seed): string
    {
        return md5('createToken' . AntragsgruenApp::getInstance()->randomSeed . $seed);
    }

    public static function getJsProtectionHint(string $seed): string
    {
        $token = static::createToken($seed);
        $str   = '<div class="alert alert-warning jsProtectionHint" role="alert" ';
        $str .= 'data-value="' . Html::encode($token) . '">';
        $str .= \Yii::t('base', 'err_js_or_login');
        $str .= '</div>';
        return $str;
    }
}
