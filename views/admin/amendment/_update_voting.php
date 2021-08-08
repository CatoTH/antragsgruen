<?php

use app\models\db\{Amendment, IMotion};
use yii\helpers\Html;


/**
 * @var Amendment $amendment
 */

$voting = $amendment->getVotingData();
$votingOpened = $voting->hasAnyData() || $amendment->status === IMotion::STATUS_VOTE || $amendment->proposalStatus === IMotion::STATUS_VOTE;
?>
<div class="contentVotingResultCaller">
    <button class="btn btn-link votingDataOpener <?= ($votingOpened ? 'hidden' : '') ?>" type="button">
        <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
        <?= Yii::t('admin', 'motion_vote_open') ?>
    </button>
</div>
<section aria-labelledby="votingDataTitle" class="votingDataHolder <?= ($votingOpened ? '' : 'hidden') ?>">
    <h2 class="green">
        <span id="votingDataTitle"><?= Yii::t('admin', 'motion_vote_title') ?></span>
        <button class="btn btn-link votingDataCloser" type="button">
            <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
            <?= Yii::t('admin', 'motion_vote_close') ?>
        </button>
    </h2>
    <div class="content">
        <div class="form-group contentVotingResultComment">
            <label class="col-md-3 control-label" for="votesComment">
                <?= Yii::t('amend', 'merge_new_votes_comment') ?>
            </label>
            <div class="col-md-9">
                <input class="form-control" name="votes[comment]" type="text" id="votesComment"
                       value="<?= Html::encode($voting->comment ?: '') ?>">
            </div>
        </div>
        <div class="contentVotingResult row">
            <div class="col-md-3">
                <label for="votesYes"><?= Yii::t('amend', 'merge_new_votes_yes') ?></label>
                <input class="form-control" name="votes[yes]" type="number" id="votesYes"
                       value="<?= Html::encode($voting->votesYes !== null ? $voting->votesYes : '') ?>">
            </div>
            <div class="col-md-3">
                <label for="votesNo"><?= Yii::t('amend', 'merge_new_votes_no') ?></label>
                <input class="form-control" name="votes[no]" type="number" id="votesNo"
                       value="<?= Html::encode($voting->votesNo !== null ? $voting->votesNo : '') ?>">
            </div>
            <div class="col-md-3">
                <label for="votesAbstention"><?= Yii::t('amend', 'merge_new_votes_abstention') ?></label>
                <input class="form-control" name="votes[abstention]" type="number" id="votesAbstention"
                       value="<?= Html::encode($voting->votesAbstention !== null ? $voting->votesAbstention : '') ?>">
            </div>
            <div class="col-md-3">
                <label for="votesInvalid"><?= Yii::t('amend', 'merge_new_votes_invalid') ?></label>
                <input class="form-control" name="votes[invalid]" type="number" id="votesInvalid"
                       value="<?= Html::encode($voting->votesInvalid !== null ? $voting->votesInvalid : '') ?>">
            </div>
            <?php
            $detailed = $voting->renderDetailedResults();
            if ($detailed) {
                echo '<div class="col-md-12">' . $detailed . '</div>';
            }
            ?>
        </div>
    </div>
</section>
