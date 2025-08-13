<?php

use app\components\IMotionStatusFilter;
use app\models\db\{Amendment, IMotion, IProposal, Motion};
use yii\helpers\Html;

/**
 * @var IMotion $imotion
 * @var IProposal $proposal
 * @var bool $limitedDisabled
 * @var bool $canBeChangedUnlimitedly
 */

$consultation = $imotion->getMyConsultation();

if ($proposal->proposalStatus === Amendment::STATUS_CUSTOM_STRING) {
    $preCustomStr = $proposal->comment;
} else {
    $preCustomStr = '';
}
if ($proposal->proposalStatus === Amendment::STATUS_REFERRED) {
    $preReferredTo = $proposal->comment;
} else {
    $preReferredTo = '';
}
if (in_array($proposal->proposalStatus, [Amendment::STATUS_OBSOLETED_BY_AMENDMENT, Motion::STATUS_OBSOLETED_BY_MOTION])) {
    $preObsoletedBy = $proposal->comment;
} else {
    $preObsoletedBy = '';
}
?>

<section class="statusDetails status_<?= Amendment::STATUS_OBSOLETED_BY_AMENDMENT ?>">
    <label class="headingLabel"><?= Yii::t('amend', 'proposal_obsoleted_by') ?>...</label>
    <?php
    $options = ['-'];
    $filter = IMotionStatusFilter::onlyUserVisible($consultation, false);
    foreach ($filter->getFilteredConsultationIMotionsSorted() as $otherMotion) {
        if (!is_a($otherMotion, Motion::class)) {
            continue;
        }
        foreach ($otherMotion->getVisibleAmendmentsSorted() as $otherAmend) {
            $options[$otherAmend->id] = $otherAmend->getTitle();
        }
    }
    $attrs = ['id' => 'obsoletedByAmendment', 'disabled' => $limitedDisabled, 'class' => 'stdDropdown'];
    echo Html::dropDownList('obsoletedByAmendment', $preObsoletedBy, $options, $attrs);
    ?>
</section>

<section class="statusDetails status_<?= Motion::STATUS_OBSOLETED_BY_MOTION ?>">
    <label class="headingLabel"><?= Yii::t('amend', 'proposal_obsoleted_by') ?>...</label>
    <?php
    $options = ['-'];
    $filter = IMotionStatusFilter::onlyUserVisible($consultation, false);
    foreach ($filter->getFilteredConsultationIMotionsSorted() as $otherMotion) {
        if (is_a($imotion, Motion::class) && $otherMotion->id === $imotion->id) {
            continue;
        }
        if (!is_a($otherMotion, Motion::class)) {
            continue;
        }
        $options[$otherMotion->id] = $otherMotion->getFormattedTitlePrefix();
    }
    $attrs = ['id' => 'obsoletedByMotion', 'disabled' => $limitedDisabled, 'class' => 'stdDropdown'];
    echo Html::dropDownList('obsoletedByMotion', $preObsoletedBy, $options, $attrs);
    ?>
</section>

<section class="statusDetails status_<?= Amendment::STATUS_REFERRED ?>">
    <label class="headingLabel" for="referredTo"><?= Yii::t('amend', 'proposal_refer_to') ?>...</label>
    <input type="text" name="referredTo" id="referredTo" value="<?= Html::encode($preReferredTo) ?>"
        <?php if (!$canBeChangedUnlimitedly) echo 'disabled'; ?>
           class="form-control">
</section>

<section class="statusDetails status_<?= Amendment::STATUS_CUSTOM_STRING ?>">
    <label class="headingLabel" for="statusCustomStr"><?= Yii::t('amend', 'proposal_custom_str') ?>:</label>
    <input type="text" name="statusCustomStr" id="statusCustomStr" value="<?= Html::encode($preCustomStr) ?>"
        <?php if (!$canBeChangedUnlimitedly) echo 'disabled'; ?>
           class="form-control">
</section>

<section class="statusDetails status_<?= Amendment::STATUS_VOTE ?>">
    <div class="votingStatus">
        <h3><?= Yii::t('amend', 'proposal_voting_status') ?></h3>
        <?php
        foreach ($consultation->getStatuses()->getVotingStatuses() as $statusId => $statusName) {
            ?>
            <label>
                <input type="radio" name="votingStatus" value="<?= $statusId ?>" <?php
                if ($imotion->votingStatus == $statusId) {
                    echo 'checked';
                }
                ?>> <?= Html::encode($statusName) ?>
            </label><br>
            <?php
        }
        ?>
    </div>
</section>
