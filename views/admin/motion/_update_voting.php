<?php

use app\components\{HTMLTools, UrlHelper};
use app\models\db\{IMotion, Motion};
use yii\helpers\Html;


/**
 * @var Motion $motion
 */

$votingBlocks = $motion->getMyConsultation()->votingBlocks;
$voting = $motion->getVotingData();
$cssClass = '';
if ($voting->hasAnyData() || $motion->proposalStatus === IMotion::STATUS_VOTE || $motion->votingBlockId !== null) {
    $cssClass .= ' hasData';
}
$voteEditUrl = UrlHelper::createUrl(['consultation/admin-votings']);
$currBlockIsLocked = ($motion->votingBlock && !$motion->votingBlock->itemsCanBeRemoved());
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
        <div class="stdTwoCols votingBlockRow">
            <label class="leftColumn control-label" for="votingBlockId">
                <?= Yii::t('amend', 'proposal_voteblock') ?>
            </label>
            <div class="rightColumn">
                <select name="votingBlockId" id="votingBlockId" class="stdDropdown"<?= ($currBlockIsLocked ? ' disabled' : '') ?>>
                    <option>-</option>
                    <?php
                    foreach ($votingBlocks as $votingBlock) {
                        echo '<option value="' . Html::encode($votingBlock->id) . '"';
                        if ($motion->votingBlockId === $votingBlock->id) {
                            echo ' selected';
                        }
                        if (!$votingBlock->itemsCanBeAdded()) {
                            echo ' disabled';
                        }
                        echo '>' . Html::encode($votingBlock->title) . '</option>';
                    }
                    ?>
                    <option value="NEW">- <?= Yii::t('amend', 'proposal_voteblock_newopt') ?> -</option>
                </select>
                <?php if ($currBlockIsLocked) { ?>
                    <small><span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <?= Yii::t('amend', 'proposal_voteblock_locked') ?></small>
                <?php } ?>
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
            $subitems = $votingBlock->getVotingItemBlocks(true, $motion);
            if (count($subitems) === 0) {
                continue;
            }
            ?>
            <div class="stdTwoCols votingItemBlockRow votingItemBlockRow<?= $votingBlock->id ?>">
                <label class="leftColumn control-label" for="votingItemBlockId<?= $votingBlock->id ?>">
                    <?= Yii::t('amend', 'proposal_voteitemblock') ?>:
                    <?= HTMLTools::getTooltipIcon(Yii::t('amend', 'proposal_voteitemblock_h')) ?>
                </label>
                <div class="rightColumn">
                    <select name="votingItemBlockId[<?= $votingBlock->id ?>]" id="votingItemBlockId<?= $votingBlock->id ?>"
                            <?= ($currBlockIsLocked ? ' disabled' : '') ?> class="stdDropdown">
                        <option value=""><?= Yii::t('amend', 'proposal_voteitemblock_none') ?></option>
                        <?php
                        foreach ($subitems as $subitem) {
                            echo '<option value="' . $subitem->groupId . '"';
                            if (in_array($motion->id, $subitem->motionIds)) {
                                echo ' selected';
                            }
                            echo ' data-group-name="' . Html::encode($subitem->groupName ?: '') . '"';
                            echo '>' . Html::encode($subitem->getTitle($motion)) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <?php
        }
        ?>
        <div class="stdTwoCols votingItemBlockNameRow votingItemBlockNameRow">
            <label class="leftColumn control-label" for="votingItemBlockName">
                <?= Yii::t('amend', 'proposal_voteitemblock_name') ?>:
            </label>
            <div class="rightColumn">
                <input name="votingItemBlockName" id="votingItemBlockName"
                       class="form-control" value="<?= Html::encode($voting->itemGroupName ?: '') ?>"
                       <?= ($currBlockIsLocked ? ' disabled' : '') ?>>
            </div>
        </div>
        <div class="stdTwoCols votingResult">
            <div class="leftColumn control-label">
                <?= Yii::t('amend', 'proposal_voting_status') ?>
            </div>
            <div class="rightColumn">
                <?php
                foreach ($motion->getMyConsultation()->getStatuses()->getVotingStatuses() as $statusId => $statusName) {
                    ?>
                    <label>
                        <input type="radio" name="votingStatus" value="<?= $statusId ?>" <?php
                        if ($motion->votingStatus == $statusId) {
                            echo 'checked';
                        }
                        ?>> <?= Html::encode($statusName) ?>
                    </label>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="stdTwoCols contentVotingResultComment">
            <label class="leftColumn control-label" for="votesComment">
                <?= Yii::t('amend', 'merge_new_votes_comment') ?>
            </label>
            <div class="rightColumn">
                <input class="form-control" name="votes[comment]" type="text" id="votesComment"
                       value="<?= Html::encode($voting->comment ?: '') ?>">
            </div>
        </div>
        <div class="contentVotingResult">
            <div>
                <label for="votesYes"><?= Yii::t('amend', 'merge_new_votes_yes') ?></label>
                <input class="form-control" name="votes[yes]" type="number" id="votesYes"
                       value="<?= Html::encode($voting->votesYes !== null ? $voting->votesYes : '') ?>">
            </div>
            <div>
                <label for="votesNo"><?= Yii::t('amend', 'merge_new_votes_no') ?></label>
                <input class="form-control" name="votes[no]" type="number" id="votesNo"
                       value="<?= Html::encode($voting->votesNo !== null ? $voting->votesNo : '') ?>">
            </div>
            <div>
                <label for="votesAbstention"><?= Yii::t('amend', 'merge_new_votes_abstention') ?></label>
                <input class="form-control" name="votes[abstention]" type="number" id="votesAbstention"
                       value="<?= Html::encode($voting->votesAbstention !== null ? $voting->votesAbstention : '') ?>">
            </div>
            <div>
                <label for="votesInvalid"><?= Yii::t('amend', 'merge_new_votes_invalid') ?></label>
                <input class="form-control" name="votes[invalid]" type="number" id="votesInvalid"
                       value="<?= Html::encode($voting->votesInvalid !== null ? $voting->votesInvalid : '') ?>">
            </div>
        </div>
        <?php
        $detailed = $voting->renderDetailedResults();
        if ($detailed) {
            echo '<div>' . $detailed . '</div>';
        }
        ?>
    </div>
</section>
