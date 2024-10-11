<?php

use app\components\Captcha;
use app\models\db\User;
use OTPHP\TOTP;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var User $user
 * @var int $pwMinLen
 * @var string|null $error
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('user', 'login_title');
$layout->addBreadcrumb(Yii::t('user', 'login_title'));
$layout->robotsNoindex = true;

echo '<h1>' . Yii::t('user', 'force_pwd_title') . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'forcedPwdForm']);
?>

    <div class="content">

        <?php
        if ($error) {
            echo '<div class="alert alert-danger"><p>' . $error . '</p></div>';
        }
        ?>

        <div class="alert alert-info">
            <p>
                <?= Yii::t('user', 'force_pwd_explanation') ?>
            </p>
        </div>

        <div class="stdTwoCols">
            <label class="leftColumn control-label" for="userPwd"><?= Yii::t('user', 'email_address') ?>:</label>
            <div class="rightColumn">
                <?= Html::encode($user->email) ?>
            </div>
        </div>
        <div class="stdTwoCols">
            <label class="leftColumn control-label" for="userPwd"><?= Yii::t('user', 'pwd_change') ?>:</label>
            <div class="rightColumn">
                <input type="password" name="pwd" value="" class="form-control" id="userPwd" data-min-len="<?= $pwMinLen ?>" required autocomplete="off">
            </div>
        </div>
        <div class="stdTwoCols">
            <label class="leftColumn control-label" for="userPwd2"><?= Yii::t('user', 'pwd_confirm') ?>:</label>
            <div class="rightColumn">
                <input type="password" name="pwd2" value="" class="form-control" id="userPwd2" required autocomplete="off">
            </div>
        </div>

        <br>

        <div class="saveRow">
            <button type="submit" class="btn btn-success" name="change"><?= Yii::t('user', 'login_btn_login') ?></button>
        </div>
    </div>


<?php
echo Html::endForm();
