<?php

use app\components\UrlHelper;
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
$layout->addLiveEventSubscription('user', 'speech');

$initData = \app\models\api\SpeechQueue::fromEntity($queue)->toUserApi($user, $cookieUser);
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
         data-queue="<?= Html::encode(json_encode($initData)) ?>"
         data-user="<?= Html::encode(json_encode($userData)) ?>"
         data-title="<?= Html::encode($queue->getTitleShort()) ?>"
         data-admin-url="<?= Html::encode($adminUrl) ?>"
         data-login-url="<?= Html::encode(UrlHelper::createUrl(['user/login', 'backUrl' => Yii::$app->request->url])) ?>"
>
    <div class="hidden" id="speechListUserTitle"><?= Html::encode($queue->getTitle()) ?></div>
    <div class="currentSpeechList"></div>
</section>
