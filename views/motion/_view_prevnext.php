<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$consultation = $motion->getMyConsultation();
if (!$consultation->getSettings()->motionPrevNextLinks) {
    return;
}

$prevMotion = null;
$nextMotion = null;
$motions = \app\components\MotionSorter::getSortedIMotionsFlat($consultation, $consultation->motions);
foreach ($motions as $idx => $itMotion) {
    if ($motion->id !== $itMotion->id) {
        continue;
    }
    if ($idx > 0) {
        $prevMotion = $motions[$idx - 1];
    }
    if ($idx < (count($motions) - 1)) {
        $nextMotion = $motions[$idx + 1];
    }
}

?>
<nav class="motionPrevNextLinks toolbarBelowTitle">
    <?php
    if ($prevMotion) {
    ?>
    <div class="prev">
        <a href="<?= Html::encode(UrlHelper::createIMotionUrl($prevMotion)) ?>">
            <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
            <?= str_replace('%TYPE%', $motion->getMyMotionType()->titleSingular, Yii::t('motion', 'prevnext_links_prev')) ?>
        </a>
    </div>
    <?php
    }
    if ($nextMotion) {
    ?>
    <div class="next">
        <a href="<?= Html::encode(UrlHelper::createIMotionUrl($nextMotion)) ?>">
            <?= str_replace('%TYPE%', $motion->getMyMotionType()->titleSingular, Yii::t('motion', 'prevnext_links_next')) ?>
            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
        </a>
    </div>
    <?php
    }
    ?>
</nav>

