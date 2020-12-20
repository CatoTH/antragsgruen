<?php

use app\components\UrlHelper;
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
$cosultation = $controller->consultation;
$layout = $controller->layoutParams;
$user = User::getCurrentUser();
$cookieUser = ($user ? null : \app\components\CookieUser::getFromCookieOrCache());

$layout->loadVue();
$layout->addVueTemplate('@app/views/speech/user-inline-widget.vue.php');

$initData = $queue->getUserApiObject($user, $cookieUser);
if ($user) {
    $userData = [
        'logged_in' => true,
        'id'        => $user->id,
        'token'     => null,
        'name'      => $user->name,
    ];
} elseif ($cookieUser) {
    $userData = [
        'logged_in' => true,
        'id'        => null,
        'token'     => $cookieUser->userToken,
        'name'      => $cookieUser->name,
    ];
} else {
    $userData = [
        'logged_in' => false,
        'id'        => null,
        'token'     => null,
        'name'      => '',
    ];
}

?>
<section aria-labelledby="speechListUserTitle"
         data-antragsgruen-widget="Frontend/CurrentSpeechList" class="currentSpeechInline"
         data-queue="<?= Html::encode(json_encode($initData)) ?>"
         data-user="<?= Html::encode(json_encode($userData)) ?>"
         data-title="<?= Html::encode($queue->getTitle()) ?>"
>
    <h2 class="green" id="speechListUserTitle"><?= Yii::t('speech', 'user_section_title') ?></h2>
    <div class="content">
        <?php
        $user = User::getCurrentUser();
        if ($user && $user->hasPrivilege($cosultation, User::PRIVILEGE_SPEECH_QUEUES)) {
            $url = UrlHelper::createUrl(['consultation/admin-speech']);
            echo '<a href="' . Html::encode($url) . '" class="speechAdminLink">';
            echo '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ';
            echo Yii::t('speech', 'goto_admin');
            echo '</a>';
        }
        ?>
        <div class="currentSpeechList"></div>
    </div>
</section>
