<?php

use yii\helpers\Html;
use app\models\db\User;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\SpeechQueue $queue
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$user       = User::getCurrentUser();

$layout->addVueTemplate('@app/views/speech/admin-widget.vue.php');
$layout->addVueTemplate('@app/views/speech/admin-subqueue.vue.php');

$initData = $queue->getAdminApiObject();

?>
<section aria-labelledby="speachListBackendTitle"
         data-antragsgruen-widget="Backend/CurrentSpeachList"
         data-queue="<?= Html::encode(json_encode($initData)) ?>">
    <h2 class="green" id="speachListBackendTitle">Redeliste verwalten</h2>
    <div class="content">
        <div class="speechAdmin"></div>
    </div>
</section>
