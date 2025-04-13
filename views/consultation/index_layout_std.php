<?php

use app\components\MotionSorter;
use app\models\db\{Amendment, Consultation, Motion, IMotion};
use app\views\consultation\LayoutHelper;

/**
 * @var yii\web\View $this
 * @var Consultation $consultation
 * @var IMotion[] $imotions
 * @var bool $isResolutionList
 */

echo '<section aria-labelledby="allMotionsTitle">';
echo '<h2 class="green" id="allMotionsTitle">' . ($isResolutionList ? Yii::t('con', 'resolutions') : Yii::t('con', 'All Motions')) . '</h2>';

$motions = MotionSorter::getSortedIMotions($consultation, $imotions);
foreach ($motions as $name => $motns) {
    echo '<ul class="motionList motionListStd motionListWithoutAgenda">';
    foreach ($motns as $motion) {
        if (is_a($motion, Motion::class)) {
            echo LayoutHelper::showMotion($motion, $consultation, false, false, 3);
        } else {
            /** @var Amendment $motion */
            echo LayoutHelper::showStatuteAmendment($motion, $consultation);
        }
    }
    echo '</ul>';
}

if (count($motions) === 0) {
    echo '<div class="content noMotionsYet">' . ($isResolutionList ? Yii::t('con', 'no_resolutions_yet') : Yii::t('con', 'no_motions_yet')) . '</div>';
}
echo '</section>';
