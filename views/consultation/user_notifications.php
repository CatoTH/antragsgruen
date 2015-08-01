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

$this->title = 'Benachrichtigungen';
$layout->addBreadcrumb('Benachrichtigungen');

echo '<h1>' . 'Benachrichtigungen' . '</h1>';

if ($user->email == '' || !$user->emailConfirmed) {
    $msg = '<strong>Keine E-Mail-Adresse</strong><br>
  Um E-Mail-Benachrichtigungen zu nutzen, musst du eine E-Mail-Adresse angegeben dund bestätigt haben.
  Du kannst das in deinen <a href="%URL%">Einstellungen</a> tun.';
    echo '<div class="content"><div class="alert alert-danger" role="alert">' .
        str_replace('%URL%', UrlHelper::createUrl('user/myaccount'), $msg) . '</div></div>';
    return;
}


$activeMotions = $activeAmendments = $activeComments = false;
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
    }
}

$action = UrlHelper::createUrl('consultation/notifications');
echo Html::beginForm($action, 'post', ['class' => 'notificationForm content']);

echo $controller->showErrors();

echo '<fieldset class="col-md-8 col-md-offset-2">
<legend>Wann willst du per E-Mail benachrichtigt werden?</legend>

  <div class="checkbox">
    <label>' .
    Html::checkbox('notifications[]', $activeMotions, ['class' => 'notiMotion', 'value' => 'motion']) .
    'Neue Anträge / Bewerbungen' .
    '</label>
  </div>

  <div class="checkbox">
    <label>' .
    Html::checkbox('notifications[]', $activeAmendments, ['class' => 'notiAmendment', 'value' => 'amendment']) .
    'Neue Änderungsanträge' .
    '</label>
  </div>

  <div class="checkbox">
    <label>' .
    Html::checkbox('notifications[]', $activeComments, ['class' => 'notiComment', 'value' => 'comment']) .
    'Neue Anträge / Bewerbungen' .
    '</label>
  </div>

</fieldset>

    <div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div>' . Html::endForm();
