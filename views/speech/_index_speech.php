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

$layout->loadVue();
$layout->addVueTemplate('@app/views/speech/user-widget.vue.php');

$initData = $queue->getUserApiObject();
if ($user) {
    $userData = [
        'loggedIn' => true,
        'id'       => $user->id,
        'name'     => $user->name,
    ];
} else {
    $userData = [
        'loggedIn' => false,
        'id'       => null,
        'name'     => '',
    ];
}

?>
<section aria-labelledby="speachListUserTitle"
         data-antragsgruen-widget="Frontend/HomeCurrentSpeachList"
         data-queue="<?= Html::encode(json_encode($initData)) ?>"
         data-user="<?= Html::encode(json_encode($userData)) ?>">
    <h2 class="green" id="speachListUserTitle">Redeliste</h2>
    <div class="content">
        <div class="currentSpeachList"></div>
    </div>
</section>
