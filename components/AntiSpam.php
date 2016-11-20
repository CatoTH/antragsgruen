<?php

namespace app\components;

use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

class AntiSpam
{
    /**
     * @param string $seed
     * @return string
     */
    public static function createToken($seed)
    {
        return md5('createToken' . AntragsgruenApp::getInstance()->randomSeed . $seed);
    }

    /**
     * @param string $seed
     * @return string
     */
    public static function getJsProtectionHint($seed)
    {
        $token = static::createToken($seed);
        $str   = '<div class="alert alert-warning jsProtectionHint" role="alert" ';
        $str .= 'data-value="' . Html::encode($token) . '">';
        $str .= \Yii::t('base', 'err_js_or_login');
        $str .= '</div>';
        return $str;
    }
}
