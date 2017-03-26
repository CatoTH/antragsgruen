<?php

use app\components\UrlHelper;
use app\models\db\User;
use app\models\db\UserNotification;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var User $user
 * @var UserNotification[] $notifications
 */

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('con', 'noti_title');
$layout->addBreadcrumb(\Yii::t('con', 'noti_title'));

echo '<h1>' . \Yii::t('con', 'noti_title') . '</h1>';

if ($user->email == '' || !$user->emailConfirmed) {
    echo '<div class="content"><div class="alert alert-danger" role="alert">' .
        str_replace('%URL%', UrlHelper::createUrl('user/myaccount'), \Yii::t('con', 'noti_err_no_email')) .
        '</div></div>';
    return;
}


$activeMotions = $activeAmendments = $activeComments = $activeAmendmentMyMotion = false;
foreach ($notifications as $noti) {
    switch ($noti->notificationType) {
        case UserNotification::NOTIFICATION_NEW_MOTION:
            $activeMotions = true;
            break;
        case UserNotification::NOTIFICATION_NEW_AMENDMENT:
            $activeAmendments = true;
            break;
        case UserNotification::NOTIFICATION_NEW_COMMENT:
            $activeComments = true;
            break;
        case UserNotification::NOTIFICATION_AMENDMENT_MY_MOTION:
            $activeAmendmentMyMotion = true;
    }
}

$action = UrlHelper::createUrl('consultation/notifications');
echo Html::beginForm($action, 'post', ['class' => 'notificationForm content']);

echo $controller->showErrors();

echo '<fieldset class="col-md-8 col-md-offset-2">
<legend>' . \Yii::t('con', 'noti_triggers') . '</legend>

  <div class="checkbox">
    <label>' .
    Html::checkbox(
        'notifications[]',
        $activeMotions,
        ['class' => 'notiMotion', 'value' => 'motion']
    ) .
    \Yii::t('con', 'noti_motions') .
    '</label>
  </div>

  <div class="checkbox">
    <label>' .
    Html::checkbox(
        'notifications[]',
        $activeAmendments,
        ['class' => 'notiAmendment', 'value' => 'amendment']
    ) .
    \Yii::t('con', 'noti_amendments') .
    '</label>
  </div>

  <div class="checkbox">
    <label>' .
    Html::checkbox(
        'notifications[]',
        $activeAmendmentMyMotion,
        ['class' => 'amendmentMyMotion', 'value' => 'amendmentMyMotion']
    ) .
    \Yii::t('con', 'noti_amendments_my_motion') .
    '</label>
  </div>

  <div class="checkbox">
    <label>' .
    Html::checkbox(
        'notifications[]',
        $activeComments,
        ['class' => 'notiComment', 'value' => 'comment']
    ) .
    \Yii::t('con', 'noti_comments') .
    '</label>
  </div>

</fieldset>

    <div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">' . \Yii::t('con', 'noti_save') . '</button>
</div>' . Html::endForm();
