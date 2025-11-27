<?php

use app\components\UrlHelper;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

/**
 * @var string $backUrl
 * @var string $providerName
 * @var string $buttonText
 * @var string $description
 */

echo '<section class="loginGenericSso">';
echo '<h2 class="green">' . Html::encode($providerName) . '</h2>';
echo '<div class="content">';

$action = AntragsgruenApp::getInstance()->domainPlain . 'sso-login';
echo Html::beginForm($action, 'post', ['id' => 'ssoLoginForm']);

$absoluteBack = UrlHelper::absolutizeLink($backUrl);
echo '<input type="hidden" name="backUrl" value="' . Html::encode($absoluteBack) . '">';
echo '<button type="submit" class="btn btn-primary" name="ssoLogin">';
echo '<span class="glyphicon glyphicon-log-in" aria-hidden="true"></span> ';
echo Html::encode($buttonText);
echo '</button>';

echo Html::endForm();
echo '</div>';

if (!empty($description)) {
    echo '<div class="ssoLoginHint">';
    echo Html::encode($description);
    echo '</div>';
}

echo '</section>';
