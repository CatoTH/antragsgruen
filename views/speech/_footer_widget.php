<?php

use app\components\{LiveDataChannels, UrlHelper};
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

$layout->provideJwt = true;
$layout->addLiveDataChannel(LiveDataChannels::ROLE_USER, LiveDataChannels::CHANNEL_SPEECH);

$initData = \app\components\Tools::getSerializer()->serialize(
    \app\models\api\speech\SpeechQueueUser::fromEntity($queue, $user, $cookieUser),
    'json'
);
$userData = new SpeechUser($user, $cookieUser);

if ($user && $user->hasPrivilege($consultation, Privileges::PRIVILEGE_SPEECH_QUEUES, null)) {
    $adminUrl = $queue->getAdminLink();
} else {
    $adminUrl = '';
}

echo $this->render('@app/views/speech/user-footer-widget.vue.php');
?>
<section class="currentSpeechFooter"
         aria-labelledby="speechListUserTitle"
         data-queue="<?= Html::encode($initData) ?>"
         data-user="<?= Html::encode(json_encode($userData)) ?>"
         data-title="<?= Html::encode($queue->getTitleShort()) ?>"
         data-admin-url="<?= Html::encode($adminUrl) ?>"
         data-login-url="<?= Html::encode(UrlHelper::createUrl(['user/login', 'backUrl' => Yii::$app->request->url])) ?>"
>
    <div class="hidden" id="speechListUserTitle"><?= Html::encode($queue->getTitle()) ?></div>
    <div class="currentSpeechList"></div>
</section>
