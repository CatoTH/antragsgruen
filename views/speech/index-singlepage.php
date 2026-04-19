<?php

use app\components\UrlHelper;
use app\models\api\SpeechUser;
use app\models\settings\Privileges;
use app\models\db\{SpeechQueue, User};
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var SpeechQueue $queue
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$layout       = $controller->layoutParams;
$layout->addBreadcrumb(Yii::t('speech', 'speaking_bc'));
$user = User::getCurrentUser();
$cookieUser = ($user ? null : \app\components\CookieUser::getFromCookieOrCache());


$layout->provideJwt = true;
$layout->addLiveEventSubscription('user', 'speech');

$initData = \app\models\api\SpeechQueue::fromEntity($queue)->toUserApi($user, $cookieUser);
$userData = new SpeechUser($user, $cookieUser);

if ($queue->motionId && $queue->motion) {
    $this->title = str_replace('%TITLE%', $queue->motion->getFormattedTitlePrefix(), Yii::t('speech', 'admin_title_to'));
} elseif ($queue->agendaItemId && $queue->agendaItem) {
    $this->title = str_replace('%TITLE%', $queue->agendaItem->title, Yii::t('speech', 'admin_title_to'));
} else {
    $this->title = Yii::t('speech', 'speaking_bc');
}

if (User::getCurrentUser()) {
    $fullscreenButton = $this->render('@app/views/shared/_fullscreen_toggle.php', [
        'init_page' => 'speech',
        'init_content_url' => null,
        'consultation' => $consultation,
    ]);
} else {
    $fullscreenButton = '';
}

?>
<div class="primaryHeader">
    <h1 id="speechListUserTitle"><?= Html::encode($this->title) ?></h1>
    <?= $fullscreenButton ?>
</div>

<?= $this->render('@app/views/speech/user-full-list-widget.vue.php') ?>

<section class="currentSpeechFullPage currentSpeechPageWidth"
         aria-labelledby="speechListUserTitle"
         data-queue="<?= Html::encode(json_encode($initData)) ?>"
         data-user="<?= Html::encode(json_encode($userData)) ?>"
         data-title="<?= Html::encode($queue->getTitle()) ?>"
>
    <?php
    $user = User::getCurrentUser();
    if ($user && $user->hasPrivilege($consultation, Privileges::PRIVILEGE_SPEECH_QUEUES, null)) {
        echo '<a href="' . Html::encode($queue->getAdminLink()) . '" class="speechAdminLink">';
        echo '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ';
        echo Yii::t('speech', 'goto_admin');
        echo '</a>';
    }
    ?>
    <div class="currentSpeechList"></div>
</section>
