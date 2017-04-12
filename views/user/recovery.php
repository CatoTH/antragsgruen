<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var string $preEmail
 * @var string $preCode
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title =\Yii::t('user', 'recover_title');
$layout->addBreadcrumb('Passwort');
$layout->robotsNoindex = true;

echo '<h1>' . \Yii::t('user', 'recover_title') . '</h1>';

echo $controller->showErrors();

$url = UrlHelper::createUrl('user/recovery');
echo Html::beginForm($url, 'post', ['class' => 'sendConfirmationForm']) . '
  <h2 class="green">' . \Yii::t('user', 'recover_step1') . '</h2>
  <div class="content">
    <div class="row">
        <div class="form-group col-sm-6">
            <label for="sendEmail">E-Mail-Adresse:</label>
            <input class="form-control" name="email" id="sendEmail" type="email" required
                placeholder="' . Html::encode(\Yii::t('user', 'recover_email_place')) . '" value="">
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <button type="submit" class="btn btn-primary" name="send">
                <span class="glyphicon glyphicon-envelope"></span>
                ' . \Yii::t('user', 'recover_send_email') . '
            </button>
        </div>
    </div>
  </div>' .
    Html::endForm() .
    Html::beginForm($url, 'post', ['class' => 'resetPasswortForm']) . '
  <h2 class="green">' . \Yii::t('user', 'recover_step2') . '</h2>
  <div class="content">
    <div class="row">
        <div class="form-group col-sm-6">
            <label for="recoveryEmail">' . \Yii::t('user', 'recover_email') . ':</label>
            <input class="form-control" name="email" id="recoveryEmail" type="email" required
                placeholder="' . Html::encode(\Yii::t('user', 'recover_email_place')) . '"
                value="' . Html::encode($preEmail) . '">
        </div>
    </div>
    <div class="row">
        <div class="form-group col-sm-6">
            <label for="recoveryCode">' . \Yii::t('user', 'recover_code') . ':</label>
            <input class="form-control" name="recoveryCode" id="recoveryCode" type="text" required
                value="' . Html::encode($preCode) . '">
        </div>
    </div>

    <div class="row">
        <div class="form-group col-sm-6">
            <label for="recoveryPassword">' . \Yii::t('user', 'recover_new_pwd') . ':</label>
            <input class="form-control" name="newPassword" id="recoveryPassword" type="password" required value="">
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <button type="submit" class="btn btn-primary" name="recover">
                <span class="glyphicon glyphicon-ok"></span>
                ' . \Yii::t('user', 'recover_set_pwd') . '
            </button>
        </div>
    </div>
  </div>
' . Html::endForm();

if ($preCode != '' && $preEmail != '') {
    $layout->addOnLoadJS('$("#recoveryPassword").scrollintoview().focus();');
}
