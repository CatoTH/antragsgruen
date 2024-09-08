<?php

use app\components\Captcha;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var string|null $error
 * @var string $captchaUsername
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

    <?php
    if (Captcha::needsCaptcha($captchaUsername)) {
        $image = Captcha::createInlineCaptcha();
        ?>
        <label for="captchaInput"><?= Yii::t('user', 'login_captcha') ?>:</label><br>
        <div class="captchaHolder">
            <img src="<?= $image ?>" alt="" width="150">
            <input type="text" value="" autocomplete="off" name="captcha" id="captchaInput" class="form-control" required>
        </div>
        <br><br>
        <?php
    }
    ?>

    <button type="submit" class="btn btn-success"><?= Yii::t('user', 'login_btn_login') ?></button>
</div>


<?php
echo Html::endForm();
