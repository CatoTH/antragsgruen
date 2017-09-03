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
            <?= \Yii::t('amend', 'merge_init_explanation') ?>
        </div>
    </div>

    <h2 class="green"><?= \Yii::t('amend', 'merge_init_all') ?></h2>
    <div class="content">
        <?php
        if ($draft) { ?>
            <div class="alert alert-info draftExistsAlert" role="alert">
                <?php
                $date = \app\components\Tools::formatMysqlDateTime($draft->dateCreation);
                echo str_replace('%DATE%', $date, \Yii::t('amend', 'merge_init_draft_hint'));

                $mergeContUrl = UrlHelper::createMotionUrl($motion, 'merge-amendments', ['resume' => $draft->id]);
                ?>
                <div class="pull-right">
                    <a href="<?= Html::encode($mergeContUrl) ?>" class="btn btn-primary">
                        <?= \Yii::t('amend', 'merge_init_all_continue') ?>
                    </a>
                </div>
            </div>
            <?php
        } ?>

        <?php
        $formUrl = UrlHelper::createMotionUrl($motion, 'merge-amendments');
        echo Html::beginForm($formUrl, 'post', ['class' => 'mergeAllRow']);
        ?>
        <div class="toMergeAmendments">
            <h3><?= \Yii::t('amend', 'merge_init_all_amendments') ?></h3>
            <ul>
                <?php
                foreach ($motion->getVisibleAmendmentsSorted() as $amend) {
                    echo '<li><label>';
                    echo Html::checkbox(
                        'amendments[' . $amend->id . ']',
                        ($amend->globalAlternative == 0),
                        ['class' => 'amendment' . $amend->id]
                    );
                    echo ' ' . Html::encode($amend->getTitle());
                    if ($amend->globalAlternative) {
                        echo ' <small>(' . \Yii::t('amend', 'global_alternative') . ')</small>';
                    }
                    echo \app\components\HTMLTools::amendmentDiffTooltip($amend);
                    echo '</label></li>';
                }
                ?>
            </ul>
        </div>
        <?php
        if ($draft) {
            ?>
            <input type="hidden" name="discard" value="1">
            <button type="submit" class="btn btn-default discard pull-right">
                <?= \Yii::t('amend', 'merge_init_all_discard') ?>
            </button>
            <?php
        } else {
            ?>
            <button type="submit" class="btn btn-primary pull-right">
                <?= \Yii::t('amend', 'merge_init_all_start') ?>
            </button>
            <?php
        }
        echo Html::endForm();
        ?>
    </div>

    <h2 class="green"><?= \Yii::t('amend', 'merge_init_single') ?></h2>
    <div class="content">
        <ul class="mergeSingle">
            <?php
            foreach ($motion->getAmendmentsRelevantForCollissionDetection() as $amendment) {
                $mergeUrl = UrlHelper::createAmendmentUrl($amendment, 'merge');
                ?>
                <li>
                    <?= \app\components\HTMLTools::amendmentDiffTooltip($amendment, 'right') ?>
                    <a href="<?= Html::encode($mergeUrl) ?>">
                        <span class="merge"><?= \Yii::t('amend', 'merge_merge') ?>:</span>
                        <span class="title"><?= Html::encode($amendment->getShortTitle()) ?></span>
                        <span class="initiator">(<?= \Yii::t('amend', 'merge1_amend_by') ?>:
                            <?= Html::encode($amendment->getInitiatorsStr()) ?>)</span>
                    </a>
                </li>
                <?php
            }
            ?>
        </ul>
    </div>
</div>