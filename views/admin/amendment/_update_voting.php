<?php

use app\models\db\{Amendment, IMotion};
use app\components\HTMLTools;
use app\components\UrlHelper;
use yii\helpers\Html;


/**
 * @var Amendment $amendment
 */

$votingBlocks = $amendment->getMyConsultation()->votingBlocks;
$voting = $amendment->getVotingData();
$cssClass = '';
if ($voting->hasAnyData() || $amendment->proposalStatus === IMotion::STATUS_VOTE) {
    $cssClass .= ' hasData';
}
$voteEditUrl = UrlHelper::createUrl(['consultation/admin-votings']);
?>
<div class="contentVotingResultCaller<?= $cssClass ?>">
    <button class="btn btn-link votingDataOpener" type="button">
        <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
        <?= Yii::t('admin', 'motion_vote_open') ?>
    </button>
</div>
<section aria-labelledby="votingDataTitle" class="votingDataHolder<?=$cssClass?>">
    <h2 class="green">
        <span id="votingDataTitle"><?= Yii::t('admin', 'motion_vote_title') ?></span>
        <button class="btn btn-link votingDataCloser" type="button">
            <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
            <?= Yii::t('admin', 'motion_vote_close') ?>
        </button>
    </h2>
    <div class="content form-horizontal">
        <div class="votingEditLinkHolder">
            <a href="<?= Html::encode($voteEditUrl) ?>" class="votingEditLink">
                <?= Yii::t('amend', 'proposal_voteblock_edit') ?>
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
            </a>
        </div>
        <div class="form-group votingBlockRow">
            <label class="col-md-3 control-label" for="votingBlockId">
                <?= Yii::t('amend', 'proposal_voteblock') ?>
            </label>
            <div class="col-md-9">
                <select name="votingBlockId" id="votingBlockId" class="stdDropdown">
                    <option>-</option>
                    <?php
                    foreach ($votingBlocks as $votingBlock) {
                        echo '<option value="' . Html::encode($votingBlock->id) . '"';
                        if ($amendment->votingBlockId === $votingBlock->id) {
                            echo ' selected';
                        }
                        echo '>' . Html::encode($votingBlock->title) . '</option>';
                    }
                    ?>
                    <option value="NEW">- <?= Yii::t('amend', 'proposal_voteblock_newopt') ?> -</option>
                </select>
                <div class="newBlock">
                    <label for="newBlockTitle" class="control-label">
                        <?= Yii::t('amend', 'proposal_voteblock_new') ?>:
                    </label>
                    <input type="text" class="form-control" id="newBlockTitle" name="newBlockTitle">
                </div>
            </div>
        </div>
        <?php
        foreach ($votingBlocks as $votingBlock) {
            $subitems = $votingBlock->getVotingItemBlocks(true, $amendment);
            if (count($subitems) === 0) {
                continue;
            }
            //echo '<pre>';            var_dump($subitems); echo '</pre>';
            ?>
            <div class="form-group votingItemBlockRow votingItemBlockRow<?= $votingBlock->id ?>">
                <label class="col-md-3 control-label" for="votingItemBlockId<?= $votingBlock->id ?>">
                    <?= Yii::t('amend', 'proposal_voteitemblock') ?>:
                    <?= HTMLTools::getTooltipIcon(Yii::t('amend', 'proposal_voteitemblock_h')) ?>
                </label>
                <div class="col-md-9">
                    <select name="votingItemBlockId[<?= $votingBlock->id ?>]" id="votingItemBlockId<?= $votingBlock->id ?>" class="stdDropdown">
                        <option value=""><?= Yii::t('amend', 'proposal_voteitemblock_none') ?></option>
                        <?php
                        foreach ($subitems as $subitem) {
                            echo '<option value="' . $subitem->groupId . '"';
                            if (in_array($amendment->id, $subitem->amendmentIds)) {
                                echo ' selected';
                            }
                            echo '>' . Html::encode($subitem->getTitle($amendment)) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <?php
        }
        ?>
        <div class="form-group votingResult">
            <div class="col-md-3 control-label">
                <?= Yii::t('amend', 'proposal_voting_status') ?>
            </div>
            <div class="col-md-9">
                <?php
                foreach ($amendment->getMyConsultation()->getStatuses()->getVotingStatuses() as $statusId => $statusName) {
                    ?>
                    <label>
                        <input type="radio" name="votingStatus" value="<?= $statusId ?>" <?php
                        if ($amendment->votingStatus == $statusId) {
                            echo 'checked';
                        }
                        ?>> <?= Html::encode($statusName) ?>
                    </label>
                    <?php
                }
                ?>
            </div>
        </div>
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
