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
$layout->addVueTemplate('@app/views/speech/user-footer-widget.vue.php');
$layout->provideJwt = true;
$layout->addLiveEventSubscription('user', 'speech');

$initData = \app\models\api\SpeechQueue::fromEntity($queue)->toUserApi($user, $cookieUser);
$userData = new SpeechUser($user, $cookieUser);

if ($user && $user->hasPrivilege($consultation, Privileges::PRIVILEGE_SPEECH_QUEUES, null)) {
    $adminUrl = $queue->getAdminLink();
} else {
    $adminUrl = '';
}

?>
<section aria-labelledby="speechListUserTitle"
         data-antragsgruen-widget="frontend/CurrentSpeechList" class="currentSpeechFooter"
         data-queue="<?= Html::encode(json_encode($initData)) ?>"
         data-user="<?= Html::encode(json_encode($userData)) ?>"
         data-title="<?= Html::encode($queue->getTitleShort()) ?>"
         data-admin-url="<?= Html::encode($adminUrl) ?>"
>
    <div class="hidden" id="speechListUserTitle"><?= Html::encode($queue->getTitle()) ?></div>
    <div class="currentSpeechList"></div>
</section>
