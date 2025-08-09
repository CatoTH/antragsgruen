<?php

use app\components\UrlHelper;
use app\views\motion\LayoutHelper;
use app\models\db\{Motion};
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var bool $top
 * @var bool $reducedNavigation
 */

if ($reducedNavigation) {
    return;
}

$prevNext = LayoutHelper::getPrevNextLinks($motion);

if (!$prevNext['prev'] && !$prevNext['next']) {
    return '';
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
    if ($prevNext['prev']) {
    ?>
    <div class="prev">
        <a href="<?= Html::encode(UrlHelper::createIMotionUrl($prevNext['prev'])) ?>">
            <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
            <?= $prevLabel ?>
        </a>
    </div>
    <?php
    }
    if ($prevNext['next']) {
    ?>
    <div class="next">
        <a href="<?= Html::encode(UrlHelper::createIMotionUrl($prevNext['next'])) ?>">
            <?= $nextLabel ?>
            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
        </a>
    </div>
    <?php
    }
    ?>
</nav>

