<?php

use app\components\Tools;
use app\models\db\Motion;
use app\models\forms\MotionDeepCopy;
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var Motion $oldMotion
 */

$locale = Tools::getCurrentDateLocale();
$date   = Tools::dateSql2bootstrapdate(date('Y-m-d'));

$voting       = $motion->getVotingData();
$votingOpened = $voting->hasAnyData();
$statusesAll = $motion->getMyConsultation()->getStatuses()->getStatusNames();

$newStatusPossibilities = [
    'resolution_final' => Yii::t('amend', 'merge_new_status_res_f'),
    'resolution_preliminary' => Yii::t('amend', 'merge_new_status_res_p'),
    'motion' => Yii::t('amend', 'merge_new_status_screened'),
];
$newResolutionProposer = '';
foreach (\app\models\settings\AntragsgruenApp::getActivePlugins() as $plugin) {
    if ($newStatuses = $plugin::getResolutionStatusOptions($motion->getMyConsultation())) {
        $newStatusPossibilities = $newStatuses;
    }
    if ($newProposer = $plugin::getResolutionProposer($oldMotion)) {
        $newResolutionProposer = $newProposer;
    }
}


?>
<h2 class="green"><?= Yii::t('amend', 'merge_new_status') ?></h2>
<div class="content contentMotionStatus">
    <div class="newMotionStatus">
        <?php
        $firstKey = array_keys($newStatusPossibilities)[0];
        foreach ($newStatusPossibilities as $key => $name) {
            echo '<label>';
            echo Html::radio('newStatus', $key === $firstKey, ['value' => $key]);
            echo ' ' . $name;
            echo '</label>';
        }
        ?>
    </div>
    <div class="newMotionInitiator">
        <label for="newInitiator"><?= Yii::t('amend', 'merge_new_orga') ?></label>
        <input class="form-control" name="newInitiator" type="text" id="newInitiator" value="<?= Html::encode($newResolutionProposer) ?>">
        <label for="dateResolution"><?= Yii::t('amend', 'merge_new_resolution_date') ?></label>
        <div class="input-group date" id="dateResolutionHolder">
            <input type="text" class="form-control" name="dateResolution" id="dateResolution"
                   value="<?= Html::encode($date) ?>" data-locale="<?= Html::encode($locale) ?>">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></span>
        </div>
        <?php
        $compatibleTypes = $motion->getMyMotionType()->getCompatibleMotionTypes([MotionDeepCopy::SKIP_NON_AMENDABLE]);
        if (count($compatibleTypes) > 1) {
            $options = [];
            foreach ($compatibleTypes as $motionType) {
                $options[$motionType->id] = $motionType->titleSingular;
            }
            $attrs = ['id' => 'motionType', 'class' => 'stdDropdown fullsize'];
            echo '<label for="newMotionType">' . Yii::t('amend', 'merge_new_motion_type') . '</label>';
            echo Html::dropDownList('newMotionType', $motion->motionTypeId, $options, $attrs);
        }
        ?>
    </div>
    <div class="newMotionSubstatus">
        <div class="title"><?= Yii::t('amend', 'merge_new_substatus') ?>:</div>
        <label>
            <input type="radio" name="newSubstatus" value="unchanged" checked>
            <?= Yii::t('amend', 'merge1_status_unchanged') ?> (<?= Html::encode($statusesAll[$oldMotion->status]) ?>)
        </label>
        <label>
            <input type="radio" name="newSubstatus" value="<?= \app\models\db\IMotion::STATUS_ACCEPTED ?>">
            <?= Yii::t('structure', 'STATUS_ACCEPTED') ?>
        </label>
        <label>
            <input type="radio" name="newSubstatus" value="<?= \app\models\db\IMotion::STATUS_MODIFIED_ACCEPTED ?>">
            <?= Yii::t('structure', 'STATUS_MODIFIED_ACCEPTED') ?>
        </label>
    </div>
</div>
<div class="content contentVotingResultCaller">
    <button class="btn btn-link votingResultOpener <?= ($votingOpened ? 'hidden' : '') ?>" type="button">
        <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
        <?= Yii::t('amend', 'merge_new_votes_enter') ?>
    </button>
    <button class="btn btn-link votingResultCloser <?= ($votingOpened ? '' : 'hidden') ?>" type="button">
        <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
        <?= Yii::t('amend', 'merge_new_votes_enter') ?>:
    </button>
</div>
<div class="content contentVotingResult <?= ($votingOpened ? '' : 'hidden') ?>">
    <div>
        <label for="votesYes"><?= Yii::t('amend', 'merge_new_votes_yes') ?></label>
        <input class="form-control" name="votes[yes]" type="number" id="votesYes"
               value="<?= Html::encode($voting->votesYes ?: '') ?>">
    </div>
    <div>
        <label for="votesNo"><?= Yii::t('amend', 'merge_new_votes_no') ?></label>
        <input class="form-control" name="votes[no]" type="number" id="votesNo"
               value="<?= Html::encode($voting->votesNo ?: '') ?>">
    </div>
    <div>
        <label for="votesAbstention"><?= Yii::t('amend', 'merge_new_votes_abstention') ?></label>
        <input class="form-control" name="votes[abstention]" type="number" id="votesAbstention"
               value="<?= Html::encode($voting->votesAbstention ?: '') ?>">
    </div>
    <div>
        <label for="votesInvalid"><?= Yii::t('amend', 'merge_new_votes_invalid') ?></label>
        <input class="form-control" name="votes[invalid]" type="number" id="votesInvalid"
               value="<?= Html::encode($voting->votesInvalid ?: '') ?>">
    </div>
</div>
<div class="content contentVotingResultComment <?= ($votingOpened ? '' : 'hidden') ?>">
    <div>
        <label for="votesComment"><?= Yii::t('amend', 'merge_new_votes_comment') ?></label>
        <input class="form-control" name="votes[comment]" type="text" id="votesComment"
               value="<?= Html::encode($voting->comment ?: '') ?>">
    </div>
</div>
