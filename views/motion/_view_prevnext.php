<?php

use app\components\UrlHelper;
use app\models\db\{Motion, User};
use app\models\settings\{PrivilegeQueryContext, Privileges};
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var bool $top
 * @var bool $reducedNavigation
 */

if ($reducedNavigation) {
    return;
}
$consultation = $motion->getMyConsultation();
if (!$consultation->getSettings()->motionPrevNextLinks) {
    return;
}

$prevMotion = null;
$nextMotion = null;

// Separate motions from resolutions for pagination
$motionsOrResolutions = array_values(array_filter($consultation->motions, fn (Motion $itMotion) => $itMotion->isResolution() === $motion->isResolution()));

$invisibleStatuses = $consultation->getStatuses()->getInvisibleMotionStatuses();
if (in_array($motion->status, $invisibleStatuses) && User::havePrivilege($consultation, Privileges::PRIVILEGE_ANY, PrivilegeQueryContext::anyRestriction())) {
    $motions = array_values(array_filter($motionsOrResolutions, fn(Motion $motion) => in_array($motion->status, $invisibleStatuses)));
    usort($motions, function(Motion $a, Motion $b) {
        return $a->getTimestamp() <=> $b->getTimestamp();
    });
} else {
    $motions = \app\components\MotionSorter::getSortedIMotionsFlat($consultation, $motionsOrResolutions);
}
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

if (!$nextMotion && !$prevMotion) {
    return;
}

if ($motion->isResolution()) {
    $prevLabel = Yii::t('motion', 'prevnext_links_prev_res');
    $nextLabel = Yii::t('motion', 'prevnext_links_next_res');
} else {
    $prevLabel = str_replace('%TYPE%', $motion->getMyMotionType()->titleSingular, Yii::t('motion', 'prevnext_links_prev'));
    $nextLabel = str_replace('%TYPE%', $motion->getMyMotionType()->titleSingular, Yii::t('motion', 'prevnext_links_next'));
}

?>
<nav class="motionPrevNextLinks <?= ($top ? 'toolbarBelowTitle' : 'toolbarAtBottom') ?>">
    <?php
    if ($prevMotion) {
    ?>
    <div class="prev">
        <a href="<?= Html::encode(UrlHelper::createIMotionUrl($prevMotion)) ?>">
            <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
            <?= $prevLabel ?>
        </a>
    </div>
    <?php
    }
    if ($nextMotion) {
    ?>
    <div class="next">
        <a href="<?= Html::encode(UrlHelper::createIMotionUrl($nextMotion)) ?>">
            <?= $nextLabel ?>
            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
        </a>
    </div>
    <?php
    }
    ?>
</nav>

