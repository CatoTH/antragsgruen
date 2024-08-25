<?php

use app\models\db\ConsultationText;
use app\models\settings\AntragsgruenApp;
use app\components\{Captcha, UrlHelper};
use app\models\db\User;
use app\models\forms\LoginUsernamePasswordForm;
use yii\helpers\Html;
use app\models\settings\Site as SiteSettings;

/**
 * @var yii\web\View $this
 * @var string|null $error
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('user', 'login_title');
$layout->addBreadcrumb(Yii::t('user', 'login_title'));
$layout->robotsNoindex = true;
$layout->addAMDModule('frontend/LoginForm');

$layout->addOnLoadJS('document.getElementById("2facode").focus()');

echo '<h1>Login</h1>';
echo Html::beginForm('', 'post', ['class' => 'tfaForm']);
?>

<div class="content">

    <div class="alert alert-info tfaIntro">
        <p><?= Yii::t('user', '2fa_login_intro') ?></p>
    </div>

    <?php
    if ($error) {
        echo '<div class="alert alert-danger tfaError"><p>' . $error . '</p></div>';
    }
    ?>

    <div class="form-group">
        <label for="2facode"><?= Yii::t('user', '2fa_enter_code') ?>:</label>
        <input type="text" name="2fa" class="form-control" id="2facode">
    </div>

    <button type="submit" class="btn btn-success"><?= Yii::t('user', 'login_btn_login') ?></button>
</div>


<?php
echo Html::endForm();
