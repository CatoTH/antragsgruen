<?php

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

$layout->loadVue();
$layout->addVueTemplate('@app/views/speech/user-widget.vue.php');

$initData = $queue->getUserApiObject();
$user     = \app\models\db\User::getCurrentUser();
if ($user) {
    $userData = [
        'loggedIn' => true,
        'name'     => $user->name,
    ];
} else {
    $userData = [
        'loggedIn' => false,
    ];
}

?>
<section aria-labelledby="speachListTitle"
         data-antragsgruen-widget="Frontend/HomeCurrentSpeachList"
         data-queue="<?= Html::encode(json_encode($initData)) ?>"
         data-user="<?= Html::encode(json_encode($userData)) ?>">
    <h2 class="green" id="speachListTitle">Redeliste</h2>
    <div class="content">
        <div class="currentSpeachList"></div>
    </div>
</section>
