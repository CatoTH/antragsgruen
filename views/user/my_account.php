<?php

use app\components\UrlHelper;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var User $user
 * @var bool $emailBlacklisted
 * @var int $pwMinLen
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('user', 'my_acc_title');
$layout->addBreadcrumb(Yii::t('user', 'my_acc_bread'));
$layout->robotsNoindex = true;
$layout->addAMDModule('frontend/AccountEdit');


$formUrl = UrlHelper::createUrl('user/myaccount');
echo '<h1>' . Yii::t('user', 'my_acc_title') . '</h1>' .
     Html::beginForm($formUrl, 'post', ['class' => 'userAccountForm content form-horizontal']);

echo $controller->showErrors();

?>

<div class="form-group">
    <label class="col-md-4 control-label" for="userName"><?= Yii::t('user', 'name') ?>:</label>
    <div class="col-md-4">
        <input type="text" name="name" value="<?= Html::encode($user->name) ?>" class="form-control"
               id="userName" required>
    </div>
</div>
<div class="form-group">
    <label class="col-md-4 control-label" for="userPwd"><?= Yii::t('user', 'pwd_change') ?>:</label>
    <div class="col-md-4">
        <input type="password" name="pwd" value="" class="form-control" id="userPwd"
               placeholder="<?= Yii::t('user', 'pwd_change_hint') ?>" data-min-len="<?= $pwMinLen ?>">
    </div>
</div>
<div class="form-group">
    <label class="col-md-4 control-label" for="userPwd2"><?= Yii::t('user', 'pwd_confirm') ?>:</label>
    <div class="col-md-4">
        <input type="password" name="pwd2" value="" class="form-control" id="userPwd2">
    </div>
</div>

<?php
if ($user->email) {
    echo '<div class="form-group emailExistingRow">
    <label class="col-md-4 control-label">' . Yii::t('user', 'email_address') . ':</label>
    <div class="col-md-8"><span class="currentEmail">';
    if ($user->emailConfirmed) {
        echo Html::encode($user->email);
    } else {
        echo '<span style="color: gray;">' . Html::encode($user->email) . '</span> ' .
             '(' . Yii::t('user', 'email_unconfirmed') . ')';
    }
    echo '</span><a href="#" class="requestEmailChange">' . Yii::t('user', 'emailchange_call') . '</a>';

    $changeRequested = $user->getChangeRequestedEmailAddress();
    if ($changeRequested) {
        echo '<div class="changeRequested">' . Yii::t('user', 'emailchange_requested') . ': ';
        echo Html::encode($changeRequested);
        echo '<button type="submit" name="resendEmailChange" class="link resendButton">' .
             Yii::t('user', 'emailchange_resend') . '</button>';
        echo '</div>';
    }

    echo '<div class="checkbox">
        <label>' . Html::checkbox('emailBlacklist', $emailBlacklisted) . Yii::t('user', 'email_blacklist') . '</label>
      </div>';

    echo '</div>
</div>';
}

?>
<div class="form-group emailChangeRow">
    <label class="col-md-4 control-label" for="userEmail"><?= Yii::t('user', 'email_address_new') ?>:</label>
    <div class="col-md-4">
        <?php
        $changeRequested = $user->getChangeRequestedEmailAddress();
        if ($changeRequested) {
            echo '<div class="changeRequested">' . Yii::t('user', 'emailchange_requested') . ': ';
            echo Html::encode($changeRequested);
            echo '<button type="submit" name="resendEmailChange" class="link resendButton">' .
                 Yii::t('user', 'emailchange_resend') . '</button>';
            echo '</div>';
        }
        ?>
        <input type="email" name="email" value="" class="form-control" id="userEmail">

    </div>
</div>

<?php
if ($user->getSettingsObj()->ppReplyTo !== '') {
    ?>
    <div class="form-group">
        <div class="col-md-4"></div>
        <div class="col-md-8">
            <?= Yii::t('user', 'email_pp_replyto') ?>:<br>
            <strong><?= Html::encode($user->getSettingsObj()->ppReplyTo) ?></strong>
        </div>
    </div>
    <?php
}
?>

<div class="saveholder">
    <button type="submit" name="save" class="btn btn-primary"><?= Yii::t('base', 'save') ?></button>
</div>
<?= Html::endForm() ?>

<br><br>

<?php
if ($controller->site) {
    ?>
    <section aria-labelledby="notificationsTitle">
        <h2 class="green" id="notificationsTitle"><?= Yii::t('user', 'notification_title') ?></h2>
        <div class="content">
            <?= Yii::t('user', 'notification_intro') ?>
            <ul>
                <?php
                foreach ($controller->site->consultations as $consultation) {
                    $link = UrlHelper::createUrl(
                        ['consultation/notifications', 'consultationPath' => $consultation->urlPath]
                    );
                    echo '<li>' . Html::a(Html::encode($consultation->title), $link) . '</li>';
                }
                ?>
            </ul>
        </div>
    </section>

    <br><br>
    <?php
}
?>

<section aria-labelledby="userDataExportTitle">
    <h2 class="green" id="userDataExportTitle"><?= Yii::t('user', 'export_title') ?></h2>
    <div class="content userDataExport">
        <?= Yii::t('user', 'export_intro') ?>
        <div class="exportRow">
            <?php
            echo Html::a(
                Yii::t('user', 'export_btn'),
                UrlHelper::createUrl('user/data-export'),
                ['class' => 'btn btn-primary']
            );
            ?>
        </div>
    </div>
</section>

<br><br>

<section aria-labelledby="delAccountTitle">
    <h2 class="green" id="delAccountTitle"><?= Yii::t('user', 'del_title') ?></h2>
    <?= Html::beginForm($formUrl, 'post', ['class' => 'accountDeleteForm content']) ?>
    <div class="accountEditExplanation alert alert-info">
        <?= Yii::t('user', 'del_explanation') ?></div>
    <div class="row">
        <div class="col-md-6">
            <div class="checkbox">
                <label><?= Html::checkbox('accountDeleteConfirm') . Yii::t('user', 'del_confirm') ?></label>
            </div>
        </div>
        <div class="col-md-6" style="text-align: right;">
            <button type="submit" name="accountDelete" class="btn btn-danger"><?= Yii::t('user', 'del_do') ?></button>
        </div>
    </div>
    <?= Html::endForm() ?>
</section>
