<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$draft      = $motion->getMergingDraft(false);

$this->title           = str_replace('%NAME%', $motion->getTitleWithPrefix(), \Yii::t('amend', 'merge_init_title'));
$layout->robotsNoindex = true;
$layout->loadFuelux();
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(\Yii::t('amend', 'merge_bread'));

?>
<h1><?= Html::encode($this->title) ?></h1>
<div class="motionMergeInit">

    <div class="content">
        <div class="alert alert-info" role="alert">
            To Do: Explanation
        </div>
    </div>

    <h2 class="green"><?= \Yii::t('amend', 'merge_init_all') ?></h2>
    <div class="content">
        <?php
        if ($draft) { ?>
            <div class="alert alert-info" role="alert">
                <?php
                $date = \app\components\Tools::formatMysqlDateTime($draft->dateCreation);
                echo str_replace('%DATE%', $date, \Yii::t('amend', 'merge_init_draft_hint'));
                ?>
            </div>
            <?php
        } ?>

        <div class="merge-all-row">
            <?php
            if ($draft) {
                $mergeContUrl = UrlHelper::createMotionUrl($motion, 'merge-amendments', ['resume' => $draft->id]);
                $mergeUrl     = UrlHelper::createMotionUrl($motion, 'merge-amendments', ['discard' => '1']);
                ?>
                <div>
                    <a href="<?= Html::encode($mergeUrl) ?>" class="btn btn-default discard">
                        <?= \Yii::t('amend', 'merge_init_all_discard') ?>
                    </a>
                </div>
                <div>
                    <a href="<?= Html::encode($mergeContUrl) ?>" class="btn btn-primary">
                        <?= \Yii::t('amend', 'merge_init_all_continue') ?>
                    </a>
                </div>
                <?php
            } else {
                $mergeUrl = UrlHelper::createMotionUrl($motion, 'merge-amendments');
                ?>
                <div>
                    <a href="<?= Html::encode($mergeUrl) ?>" class="btn btn-primary">
                        <?= \Yii::t('amend', 'merge_init_all_start') ?>
                    </a>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

    <h2 class="green"><?= \Yii::t('amend', 'merge_init_single') ?></h2>
    <div class="content">
        <ul class="merge-single">
            <?php
            foreach ($motion->getAmendmentsRelevantForCollissionDetection() as $amendment) {
                $mergeUrl = UrlHelper::createAmendmentUrl($amendment, 'merge');
                ?>
                <li>
                    <?= \app\components\HTMLTools::amendmentDiffTooltip($amendment, 'right') ?>
                    <a href="<?= Html::encode($mergeUrl) ?>">
                        <span class="merge">Merge:</span>
                        <span class="title"><?= Html::encode($amendment->getShortTitle()) ?></span>
                        <span class="initiator">(By: <?= Html::encode($amendment->getInitiatorsStr()) ?>)</span>
                    </a>
                </li>
                <?php
            }
            ?>
        </ul>
    </div>
</div>