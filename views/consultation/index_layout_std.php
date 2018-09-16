<?php

use app\components\MotionSorter;
use app\models\db\Consultation;
use app\views\consultation\LayoutHelper;

/**
 * @var yii\web\View $this
 * @var Consultation $consultation
 */

list($motions, $resolutions) = MotionSorter::getMotionsAndResolutions($consultation->motions);
if (count($resolutions) > 0) {
    echo $this->render('_index_resolutions', ['consultation' => $consultation, 'resolutions' => $resolutions]);
}

echo '<h2 class="green">' . \Yii::t('con', 'All Motions') . '</h2>';

$motions = MotionSorter::getSortedMotions($consultation, $motions);
foreach ($motions as $name => $motns) {
    echo '<ul class="motionList motionListStd motionListWithoutAgenda">';
    foreach ($motns as $motion) {
        LayoutHelper::showMotion($motion, $consultation);
    }
    echo '</ul>';
}

if (count($motions) === 0) {
    echo '<div class="content noMotionsYet">' . \Yii::t('con', 'no_motions_yet') . '</div>';
}
