<?php

use app\components\MotionSorter;
use app\models\db\Consultation;
use app\views\consultation\LayoutHelper;

/**
 * @var Consultation $consultation
 */

$motions = MotionSorter::getSortedMotions($consultation, $consultation->motions);
foreach ($motions as $name => $motns) {
    echo "<ul class='motionListStd'>";
    foreach ($motns as $motion) {
        LayoutHelper::showMotion($motion, $consultation);
    }
    echo "</ul>";
}
