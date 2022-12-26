<?php

use app\components\UrlHelper;
use app\models\db\{User, UserNotification};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var User $user
 * @var UserNotification[] $notifications
 */

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('con', 'noti_title');
$layout->addBreadcrumb(Yii::t('con', 'noti_title'));

echo '<h1>' . Yii::t('con', 'noti_title') . '</h1>';

if ($user->email == '' || !$user->emailConfirmed) {
    echo '<div class="content"><div class="alert alert-danger" role="alert">' .
        str_replace('%URL%', UrlHelper::createUrl('user/myaccount'), Yii::t('con', 'noti_err_no_email')) .
        '</div></div>';
    return;
}


$commentSettingOptions = [
    UserNotification::COMMENT_REPLIES             => Yii::t('con', 'noti_comments_replies'),
    UserNotification::COMMENT_SAME_MOTIONS        => Yii::t('con', 'noti_comments_motions'),
    UserNotification::COMMENT_ALL_IN_CONSULTATION => Yii::t('con', 'noti_comments_con'),
];

$amendmentSettingOptions = [
    0 => Yii::t('con', 'noti_amendments_mine'),
    1 => Yii::t('con', 'noti_amendments_all'),
];

$activeMotions    = $activeAmendments = $activeComments = false;
$commentSetting   = UserNotification::COMMENT_SETTINGS[0];
$amendmentSetting = 0;

foreach ($notifications as $noti) {
    switch ($noti->notificationType) {
        case UserNotification::NOTIFICATION_NEW_MOTION:
            $activeMotions = true;
            break;
        case UserNotification::NOTIFICATION_NEW_AMENDMENT:
            $activeAmendments = true;
            if ($amendmentSetting === 0) {
                $amendmentSetting = 1;
            }
            break;
        case UserNotification::NOTIFICATION_NEW_COMMENT:
            $activeComments = true;
            $commentSetting = $noti->getSettingByKey('comments', UserNotification::COMMENT_SETTINGS[0]);
            break;
        case UserNotification::NOTIFICATION_AMENDMENT_MY_MOTION:
            $activeAmendments = true;
            break;
    }
}


$action = UrlHelper::createUrl('consultation/notifications');
echo Html::beginForm($action, 'post', ['class' => 'notificationForm content']);

echo $controller->showErrors();


?>
    <fieldset class="content" data-antragsgruen-widget="frontend/UserNotificationsForm">
        <legend><?= Yii::t('con', 'noti_triggers') ?></legend>

        <div class="notificationRow">
            <label class="notiMotion">
                <?= Html::checkbox('notifications[motion]', $activeMotions) ?>
                <?= Yii::t('con', 'noti_motions') ?>
            </label>
        </div>

        <div class="notificationRow">
            <label class="notiAmendment">
                <?= Html::checkbox('notifications[amendment]', $activeAmendments) ?>
                <?= Yii::t('con', 'noti_amendments') ?>
            </label>
            <div class="amendmentSettings radioList">
                <?= Html::radioList('notifications[amendmentsettings]', $amendmentSetting, $amendmentSettingOptions) ?>
            </div>
        </div>

        <div class="notificationRow">
            <label class="notiComment">
                <?= Html::checkbox('notifications[comment]', $activeComments) ?>
                <?= Yii::t('con', 'noti_comments') ?>
            </label>
            <div class="commentSettings radioList">
                <?= Html::radioList('notifications[commentsetting]', $commentSetting, $commentSettingOptions) ?>
            </div>
        </div>
    </fieldset>

    <div class="saveholder">
        <button type="submit" name="save" class="btn btn-primary"><?= Yii::t('con', 'noti_save') ?></button>
    </div>

<?php
echo Html::endForm();
