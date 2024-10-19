<?php

declare(strict_types=1);

use app\components\{Captcha, UrlHelper};
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var string $errors
 * @var string $prefillCode
 * @var string $backUrl
 * @var string $email
 * @var \app\models\db\User|null $allowResend
 */

$this->title = Yii::t('user', 'confirm_title');

?>
<h1><?= Yii::t('user', 'confirm_title') ?></h1>
<div class="content">

<?php
if ($errors != '') {
    echo '<div class="alert alert-danger" role="alert">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">' . Yii::t('base', 'aria_error') . ':</span>' . Html::encode($errors) . '</div>';
} else {
    echo '<div class="alert alert-info" role="alert">
        <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
        ' . Yii::t('user', 'confirm_mail_sent') . '
    </div>';
}

$params = ['user/confirmregistration', 'backUrl' => $backUrl];
if ($email) {
    $params['email'] = $email;
}

echo Html::beginForm(UrlHelper::createUrl($params), 'post', ['id' => 'confirmAccountForm']);
?>

    <div class="inputHolder">
        <label for="username"><?= Yii::t('user', 'confirm_username') ?>:</label>
        <input type="text" value="<?= Html::encode($email) ?>" id="username" name="email" class="form-control"
            <?php if ($email != '') echo "disabled"; ?>
        >
    </div>

    <div class="inputHolder">
        <label for="code"><?= Yii::t('user', 'confirm_code') ?>:</label>
        <input type="text" name="code" value="<?= Html::encode($prefillCode) ?>" id="code" class="form-control" autocomplete="off">
    </div>

    <?php
    if (Captcha::needsCaptcha(null)) {
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

    <div class="saveResetRow">
        <div class="save">
            <input type="submit" value="<?= Yii::t('user', 'confirm_btn_do') ?>" class="btn btn-primary">
        </div>

        <?php if ($allowResend) { ?>
        <div class="resend">
            <button type="submit" class="btn btn-link" name="resend">
                <?= Yii::t('user', 'confirm_resend') ?>
            </button>
        </div>
        <?php } ?>
    </div>

    <?= Html::endForm() ?>

</div>
