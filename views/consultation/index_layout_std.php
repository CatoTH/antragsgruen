<?php

use app\components\MotionSorter;
use app\models\db\Consultation;
use app\views\consultation\LayoutHelper;

/**
 * @var Consultation $consultation
 */

echo '<h2 class="green">' . \Yii::t('con', 'All Motions') . '</h2>';

$motions = MotionSorter::getSortedMotions($consultation, $consultation->motions);
foreach ($motions as $name => $motns) {
    echo '<ul class="motionListStd layout1">';
    foreach ($motns as $motion) {
        LayoutHelper::showMotion($motion, $consultation);
    }
    echo '</ul>';
}

if (count($motions) === 0) {
    echo '<div class="content noMotionsYet">' . \Yii::t('con', 'no_motions_yet') . '</div>';
}
