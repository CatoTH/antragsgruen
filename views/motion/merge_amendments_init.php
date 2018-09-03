<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var Motion|null $draft
 * @var Motion|null $unconfirmed
 * @var Amendment[] $amendments
 */

/** @var \app\controllers\Base $controller */
$controller  = $this->context;
$layout      = $controller->layoutParams;

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
        if ($unconfirmed) { ?>
            <div class="alert alert-info unconfirmedExistsAlert" role="alert">
                <?php
                echo \Yii::t('amend', 'merge_init_unconf_hint');
                $confirmUrl = UrlHelper::createMotionUrl($unconfirmed, 'merge-amendments-confirm');
                ?>
                <div class="pull-right">
                    <a href="<?= Html::encode($confirmUrl) ?>" class="btn btn-primary">
                        <?= \Yii::t('amend', 'merge_init_unconf_btn') ?>
                    </a>
                </div>
            </div>
            <?php
        }
        if ($draft) { ?>
            <div class="alert alert-info draftExistsAlert" role="alert">
                <?php
                $date = \app\components\Tools::formatMysqlDateTime($draft->dateCreation);
                echo str_replace('%DATE%', $date, \Yii::t('amend', 'merge_init_draft_hint'));

                $mergeContUrl = UrlHelper::createMotionUrl($motion, 'merge-amendments', ['resume' => $draft->id]);
                ?>
                <div class="pull-right">
                    <a href="<?= Html::encode($mergeContUrl) ?>" class="btn btn-primary">
                        <?= \Yii::t('amend', 'merge_init_draft_btn') ?>
                    </a>
                </div>
            </div>
            <?php
        } ?>

        <?php
        $formUrl = UrlHelper::createMotionUrl($motion, 'merge-amendments');
        echo Html::beginForm($formUrl, 'post', ['class' => 'mergeAllRow']);

        $hasProposals = $hasProposalText = false;
        foreach ($motion->getVisibleAmendmentsSorted() as $amend) {
            if ($amend->proposalStatus !== null) {
                $hasProposals = true;
            }
            if ($amend->proposalReference) {
                $hasProposalText = true;
            }
        }
        ?>
        <div class="toMergeAmendments">
            <table class="mergeTable">
                <thead>
                <tr>
                    <th class="colCheck"><?= \Yii::t('amend', 'merge_amtable_merge') ?></th>
                    <th class="colTitle"><?= \Yii::t('amend', 'merge_amtable_title') ?></th>
                    <th class="colStatus"><?= \Yii::t('amend', 'merge_amtable_status') ?></th>
                    <?php
                    if ($hasProposals) {
                        ?>
                        <th class="colProposal"><?= \Yii::t('amend', 'merge_amtable_proposal') ?></th>
                        <?php
                    }
                    ?>
                    <th class="colText"><?= \Yii::t('amend', 'merge_amtable_text') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($amendments as $amend) {
                    $id = 'markAmendment' . $amend->id;
                    echo '<tr class="amendment' . $amend->id . '"><td class="colCheck">';
                    echo Html::checkbox(
                        'amendments[' . $amend->id . ']',
                        $amend->markForMergingByDefault(),
                        ['class' => 'amendment' . $amend->id, 'id' => $id]
                    );
                    echo '</td><td class="colTitle">';
                    echo '<label for="' . $id . '">' . Html::encode($amend->titlePrefix) . '</label>';
                    if ($amend->globalAlternative) {
                        echo ' <small>(' . \Yii::t('amend', 'global_alternative') . ')</small>';
                    }
                    echo '</td><td class="colStatus">';
                    echo $amend->getFormattedStatus();
                    echo '</td>';
                    if ($hasProposals) {
                        echo '<td class="colProposal">' . $amend->getFormattedProposalStatus() . '</td>';
                    }
                    if ($amend->proposalReference) {
                        echo '<td class="colText hasAlternative">';
                        echo '<label class="textOriginal">';
                        echo '<input type="radio" name="textVersion[' . $amend->id . ']" value="original"> ';
                        echo \Yii::t('amend', 'merge_amtable_text_orig');
                        echo \app\components\HTMLTools::amendmentDiffTooltip($amend, 'bottom');
                        echo '</label>';

                        echo '<label class="textProposal">';
                        echo '<input type="radio" name="textVersion[' . $amend->id . ']" value="proposal" checked> ';
                        echo \Yii::t('amend', 'merge_amtable_text_prop');
                        echo \app\components\HTMLTools::amendmentDiffTooltip($amend->proposalReference, 'bottom');
                        echo '</label>';
                        echo '</td>';
                    } else {
                        echo '<td class="colText hasAlternative">';
                        echo \app\components\HTMLTools::amendmentDiffTooltip($amend, 'bottom');
                        echo '</td>';
                    }
                    echo '</tr>' . "\n";
                }
                ?>
                </tbody>
            </table>
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
            foreach ($motion->getAmendmentsRelevantForCollisionDetection() as $amendment) {
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