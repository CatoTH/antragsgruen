<?php

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
$layout     = $controller->layoutParams;
$user       = User::getCurrentUser();
$cookieUser = ($user ? null : \app\components\CookieUser::getFromCookieOrCache());

$layout->loadVue();
$layout->addVueTemplate('@app/views/speech/user-footer-widget.vue.php');

$initData = $queue->getUserApiObject($user, $cookieUser);
if ($user) {
    $userData = [
        'loggedIn' => true,
        'id'       => $user->id,
        'token'    => null,
        'name'     => $user->name,
    ];
} elseif ($cookieUser) {
    $userData = [
        'loggedIn' => true,
        'id'       => null,
        'token'    => $cookieUser->userToken,
        'name'     => $user->name,
    ];
} else {
    $userData = [
        'loggedIn' => false,
        'id'       => null,
        'token'    => null,
        'name'     => '',
    ];
}

?>
<section aria-labelledby="speechListUserTitle"
         data-antragsgruen-widget="Frontend/CurrentSpeechList" class="currentSpeechFooter"
         data-queue="<?= Html::encode(json_encode($initData)) ?>"
         data-user="<?= Html::encode(json_encode($userData)) ?>"
         data-title="<?= Html::encode($queue->getTitle()) ?>"
>
    <div class="hidden" id="speechListUserTitle"><?= Html::encode($queue->getTitle()) ?></div>
    <div class="currentSpeechList"></div>
</section>
