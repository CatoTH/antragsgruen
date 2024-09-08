<?php

use app\components\Captcha;
use OTPHP\TOTP;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var string|null $error
 * @var TOTP|null $addSecondFactorKey
 * @var string $captchaUsername
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('user', 'login_title');
$layout->addBreadcrumb(Yii::t('user', 'login_title'));
$layout->robotsNoindex = true;

echo '<h1>' . Yii::t('user', '2fa_register_title') . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'forcedTfaForm']);
?>

    <div class="content">

        <?php
        if ($error) {
            echo '<div class="alert alert-danger"><p>' . $error . '</p></div>';
        }
        ?>

        <div class="alert alert-info">
            <p>
                <?= Yii::t('user', '2fa_register_explanation') ?><br><br>
                <?= Yii::t('user', '2fa_general_explanation') ?>
            </p>
        </div>

        <?php
        $result = \app\components\SecondFactorAuthentication::createQrCode($addSecondFactorKey);
        ?>
        <div class="secondFactorAdderBody">
            <div>
                <h3><?= Yii::t('user', '2fa_add_step1') ?></h3>
                <img src="<?= $result->getDataUri() ?>" alt="<?= Yii::t('user', '2fa_img_alt') ?>" class="tfaqr">
            </div>
            <h3><?= Yii::t('user', '2fa_add_step2') ?></h3>
            <label class="setFaField">
                <?= Yii::t('user', '2fa_enter_code') ?>:
                <input type="text" name="set2fa" class="form-control">
            </label>

            <?php
            if (Captcha::needsCaptcha($captchaUsername)) {
                $image = Captcha::createInlineCaptcha();
                ?>
                <div class="captchaForm">
                    <label for="captchaInput"><?= Yii::t('user', 'login_captcha') ?>:</label><br>
                    <div class="captchaHolder">
                        <img src="<?= $image ?>" alt="" width="150">
                        <input type="text" value="" autocomplete="off" name="captcha" id="captchaInput" class="form-control" required>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="saveRow">
            <button type="submit" class="btn btn-success"><?= Yii::t('user', 'login_btn_login') ?></button>
        </div>
    </div>


<?php
echo Html::endForm();
