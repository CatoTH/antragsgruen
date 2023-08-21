<?php

use app\models\api\SpeechUser;
use app\models\settings\Privileges;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\SpeechQueue $queue
 */

if (!$queue) {
    return;
}

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$layout = $controller->layoutParams;
$user = User::getCurrentUser();
$cookieUser = ($user ? null : \app\components\CookieUser::getFromCookieOrCache());

$layout->loadVue();
$layout->addVueTemplate('@app/views/speech/_speech_common_mixins.vue.php');
$layout->addVueTemplate('@app/views/speech/user-inline-widget.vue.php');
$layout->provideJwt = true;
$layout->addLiveEventSubscription('user', 'speech');

$initData = \app\models\api\SpeechQueue::fromEntity($queue)->toUserApi($user, $cookieUser);
$userData = new SpeechUser($user, $cookieUser);

if ($queue->motionId || $queue->agendaItemId) {
    $title = $queue->getTitle();
} else {
    $title = Yii::t('speech', 'user_section_title');
}

?>
<section class="currentSpeechInline currentSpeechPageWidth"
         aria-labelledby="speechListUserTitle"
         data-antragsgruen-widget="frontend/CurrentSpeechList"
         data-queue="<?= Html::encode(json_encode($initData)) ?>"
         data-user="<?= Html::encode(json_encode($userData)) ?>"
         data-title="<?= Html::encode($queue->getTitle()) ?>"
>
    <h2 class="green" id="speechListUserTitle"><?php
        echo Html::encode($title);

        $user = User::getCurrentUser();
        if ($user && $user->hasPrivilege($consultation, Privileges::PRIVILEGE_SPEECH_QUEUES, null)) {
            echo '<a href="' . Html::encode($queue->getAdminLink()) . '" class="speechAdminLink greenHeaderExtraLink">';
            echo '<span class="glyphicon glyphicon-wrench" aria-hidden="true"></span> ';
            echo Yii::t('speech', 'goto_admin');
            echo '</a>';
        }
        ?></h2>
    <div class="content">
        <div class="currentSpeechList"></div>
    </div>
</section>
