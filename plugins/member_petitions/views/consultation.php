<?php

use app\plugins\member_petitions\Tools;
use app\models\db\Consultation;
use app\models\settings\Layout;

/**
 * @var \yii\web\View $this
 * @var Consultation $consultation
 * @var Layout $layout
 * @var bool $admin
 */

$layout->bodyCssClasses[] = 'memberPetitionList memberPetitionConsultation';

$missing = false;
if (!Tools::getDiscussionType($consultation)) {
    echo '<div class="alert alert-danger">No discussion motion type is configured yet.</div>';
    $missing = true;
}
if (!Tools::getPetitionType($consultation)) {
    echo '<div class="alert alert-danger">No petition motion type is configured yet.</div>';
    $missing = true;
}
if ($missing) {
    return;
}

echo $this->render('_motion_sorter', ['myConsultations' => [$consultation], 'bold' => '']);
