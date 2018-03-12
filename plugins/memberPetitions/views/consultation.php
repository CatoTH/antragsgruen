<?php

use app\plugins\memberPetitions\Tools;
use app\models\db\Consultation;
use app\models\settings\Layout;

/**
 * @var \yii\web\View $this
 * @var Consultation $consultation
 * @var Layout $layout
 * @var bool $admin
 */

if (!Tools::getDiscussionType($consultation)) {
    echo '<div class="alert alert-danger">No discussion motion type is configured yet.</div>';
}
if (!Tools::getPetitionType($consultation)) {
    echo '<div class="alert alert-danger">No petition motion type is configured yet.</div>';
}

?>

<h2 class="green">
    <?=\Yii::t('memberpetitions', 'status_answered') ?>
</h2>
<div class="content">
    <?= $this->render('_motion_list', ['motions' => Tools::getMotionsAnswered($consultation)]) ?>
</div>


<h2 class="green">
    <?=\Yii::t('memberpetitions', 'status_unanswered') ?>
</h2>
<div class="content">
    <?= $this->render('_motion_list', ['motions' => Tools::getMotionsUnanswered($consultation)]) ?>
</div>


<h2 class="green">
    <?=\Yii::t('memberpetitions', 'status_collecting') ?>
</h2>
<div class="content">
    <?= $this->render('_motion_list', ['motions' => Tools::getMotionsCollecting($consultation)]) ?>
</div>

