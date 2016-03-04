<?php

use app\components\UrlHelper;
use app\models\forms\LoginUsernamePasswordForm;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var string $preEmail
 * @var string $preCode
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Passwort zurücksetzen';
$layout->addBreadcrumb('Passwort');
$layout->robotsNoindex = true;

echo '<h1>' . 'Passwort zurücksetzen' . '</h1>';

echo $controller->showErrors();

$url = UrlHelper::createUrl('user/recovery');
echo Html::beginForm($url, 'post', ['class' => 'sendConfirmationForm']) . '
  <h2 class="green">' . '1. Gib deine E-Mail-Adresse ein' . '</h2>
  <div class="content">
    <div class="row">
        <div class="form-group col-sm-6">
            <label for="sendEmail">E-Mail-Adresse:</label>
            <input class="form-control" name="email" id="sendEmail" type="email" required
                placeholder="meine@email-adresse.de" value="">
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <button type="submit" class="btn btn-primary" name="send">
                <span class="glyphicon glyphicon-envelope"></span>
                Bestätigungs-Mail schicken
            </button>
        </div>
    </div>
  </div>' .
    Html::endForm() .
    Html::beginForm($url, 'post', ['class' => 'resetPasswortForm']) . '
  <h2 class="green">' . '2. Setze ein neues Passwort' . '</h2>
  <div class="content">
    <div class="row">
        <div class="form-group col-sm-6">
            <label for="recoveryEmail">E-Mail-Adresse:</label>
            <input class="form-control" name="email" id="recoveryEmail" type="email" required
                placeholder="meine@email-adresse.de" value="' . Html::encode($preEmail) . '">
        </div>
    </div>
    <div class="row">
        <div class="form-group col-sm-6">
            <label for="recoveryCode">Bestätigungs-Code:</label>
            <input class="form-control" name="recoveryCode" id="recoveryCode" type="text" required
                value="' . Html::encode($preCode) . '">
        </div>
    </div>

    <div class="row">
        <div class="form-group col-sm-6">
            <label for="recoveryPassword">Neues Passwort:</label>
            <input class="form-control" name="newPassword" id="recoveryPassword" type="password" required value="">
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <button type="submit" class="btn btn-primary" name="recover">
                <span class="glyphicon glyphicon-ok"></span>
                Neues Passwort setzen
            </button>
        </div>
    </div>
  </div>
' . Html::endForm();

if ($preCode != '' && $preEmail != '') {
    $layout->addOnLoadJS('$("#recoveryPassword").scrollintoview().focus();');
}
