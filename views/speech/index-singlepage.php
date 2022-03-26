<?php

use app\components\UrlHelper;
use app\models\api\SpeechUser;
use app\models\db\{ConsultationUserGroup, SpeechQueue, User};
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

$initData = $queue->getUserApiObject($user, $cookieUser);
$userData = new SpeechUser($user, $cookieUser);

if ($queue->motion) {
    $this->title = str_replace('%TITLE%', $queue->motion->titlePrefix, Yii::t('speech', 'admin_title_to'));
} else {
    $this->title = Yii::t('speech', 'speaking_bc');
}
?>
<h1 id="speechListUserTitle"><?= Html::encode($this->title) ?></h1>

<section class="currentSpeechFullPage currentSpeechPageWidth"
         aria-labelledby="speechListUserTitle"
         data-antragsgruen-widget="frontend/CurrentSpeechList"
         data-queue="<?= Html::encode(json_encode($initData)) ?>"
         data-user="<?= Html::encode(json_encode($userData)) ?>"
         data-title="<?= Html::encode($queue->getTitle()) ?>"
>
    <?php
    $user = User::getCurrentUser();
    if ($user && $user->hasPrivilege($consultation, ConsultationUserGroup::PRIVILEGE_SPEECH_QUEUES)) {
        $url = UrlHelper::createUrl(['consultation/admin-speech']);
        echo '<a href="' . Html::encode($url) . '" class="speechAdminLink">';
        echo '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ';
        echo Yii::t('speech', 'goto_admin');
        echo '</a>';
    }
    ?>
    <div class="currentSpeechList"></div>
</section>
