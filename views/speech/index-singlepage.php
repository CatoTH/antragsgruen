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


$layout->loadVue();
$layout->addVueTemplate('@app/views/speech/_speech_common_mixins.vue.php');
$layout->addVueTemplate('@app/views/speech/user-full-list-widget.vue.php');
$layout->addFullscreenTemplates();
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
    $fullscreenInitData = json_encode([
        'consultation_url' => UrlHelper::createUrl(['/consultation/rest']),
        'init_page' => 'speech'
    ]);
    $fullscreenButton = '<button type="button" title="' . Yii::t('motion', 'fullscreen') . '" class="btn btn-link btnFullscreen"
        data-antragsgruen-widget="frontend/FullscreenToggle" data-vue-element="fullscreen-projector" data-vue-initdata="' . Html::encode($fullscreenInitData) . '">
        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
        <span class="sr-only">' . Yii::t('motion', 'fullscreen') . '</span>
    </button>';
} else {
    $fullscreenButton = '';
}

?>
<div class="primaryHeader">
    <h1 id="speechListUserTitle"><?= Html::encode($this->title) ?></h1>
    <?= $fullscreenButton ?>
</div>

<section class="currentSpeechFullPage currentSpeechPageWidth"
         aria-labelledby="speechListUserTitle"
         data-antragsgruen-widget="frontend/CurrentSpeechList"
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
