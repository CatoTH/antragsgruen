<?php

/**
 * @var Yii\web\View $this
 * @var Amendment $amendment
 * @var AmendmentProposal $proposal
 * @var string $context
 */

use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\components\{IMotionStatusFilter, Tools, UrlHelper};
use app\models\db\{Amendment, AmendmentProposal, Motion, User};
use yii\helpers\Html;

$collidingAmendments = $proposal->collidesWithOtherProposedAmendments(true);

$saveUrl = UrlHelper::createAmendmentUrl($amendment, 'save-proposal-status');
$isLatestVersion = ($proposal->id === $amendment->getLatestProposal()->id);
echo Html::beginForm($saveUrl, 'POST', [
    'id'                       => 'proposedChanges',
    'class'                    => ($isLatestVersion ? 'latestVersion' : 'oldVersion'),
    'data-antragsgruen-widget' => 'backend/ChangeProposedProcedure',
    'data-context'             => $context,
    'data-proposal-id'         => ($proposal->isNewRecord ? null : $proposal->id),
]);
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
if ($proposal->proposalStatus === Amendment::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION) {
    $preMovedToMotion = $proposal->comment;
} else {
    $preMovedToMotion = '';
}
if ($proposal->proposalStatus === Amendment::STATUS_CUSTOM_STRING) {
    $preCustomStr = $proposal->comment;
} else {
    $preCustomStr = '';
}

if (isset($msgAlert)) {
    echo '<div class="alert alert-info">' . $msgAlert . '</div>';
}

$consultation = $amendment->getMyConsultation();
$canBeChangedUnlimitedly = $proposal->canEditProposedProcedure();
$limitedDisabled = ($canBeChangedUnlimitedly ? null : true);
?>
<h2>
    <?= Yii::t('amend', 'proposal_amend_title') ?>
    <button class="pull-right btn-link closeBtn" type="button"
            title="<?= Html::encode(Yii::t('amend', 'proposal_close')) ?>">
        <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
    </button>
</h2>

<?= $this->render('../shared/_proposed_procedure_versions', ['imotion' => $amendment, 'proposal' => $proposal]) ?>

<div class="holder">
        <section class="statusForm">
            <h3><?= Yii::t('amend', 'proposal_status_title') ?></h3>

            <?php
            $foundStatus = false;
            foreach ($consultation->getStatuses()->getAmendmentProposedProcedureStatuses() as $statusId => $statusName) {
                ?>
                <label class="proposalStatus<?= $statusId ?>">
                    <input type="radio" name="proposalStatus" value="<?= $statusId ?>"<?php
                    if ($proposal->proposalStatus == intval($statusId)) {
                        $foundStatus = true;
                        echo ' checked';
                    }
                    if (!$canBeChangedUnlimitedly) {
                        echo ' disabled';
                    }
                    ?>> <?= Html::encode($statusName) ?>
                </label><br>
                <?php
            }
            ?>
            <label>
                <?= Html::radio('proposalStatus', !$foundStatus, ['value' => '0', 'disabled' => $limitedDisabled]) ?>
                - <?= Yii::t('amend', 'proposal_status_na') ?> -
            </label>
        </section>
        <div class="middleCol">
            <div class="visibilitySettings showIfStatusSet">
                <h3><?= Yii::t('amend', 'proposal_publicity') ?></h3>
                <label>
                    <?= Html::checkbox('proposalVisible', ($proposal->visibleFrom !== null), ['disabled' => $limitedDisabled]) ?>
                    <?= Yii::t('amend', 'proposal_visible') ?>
                </label>
                <label>
                    <?= Html::checkbox('setPublicExplanation', ($proposal->explanation !== null), ['disabled' => $limitedDisabled]) ?>
                    <?= Yii::t('amend', 'proposal_public_expl_set') ?>
                </label>
            </div>

            <?= $this->render('../shared/_proposed_procedure_votings', ['imotion' => $amendment]) ?>

            <?= $this->render('../shared/_proposed_procedure_feedback_status', ['imotion' => $amendment, 'proposal' => $proposal]) ?>
        </div>
        <?= $this->render('../shared/_proposed_procedure_log', ['imotion' => $amendment]) ?>
    </div>

    <?= $this->render('../shared/_proposed_procedure_tags', ['imotion' => $amendment]) ?>

    <section class="statusDetails status_<?= Amendment::STATUS_OBSOLETED_BY_AMENDMENT ?>">
        <label class="headingLabel"><?= Yii::t('amend', 'proposal_obsoleted_by') ?>...</label>
        <?php
        $options = ['-'];
        $filter = IMotionStatusFilter::onlyUserVisible($consultation, false);
        foreach ($amendment->getMyMotion()->getVisibleAmendmentsSorted() as $otherAmend) {
            if ($otherAmend->id !== $amendment->id) {
                $options[$otherAmend->id] = $otherAmend->getTitle();
            }
        }
        foreach ($filter->getFilteredConsultationIMotionsSorted() as $otherMotion) {
            if ($otherMotion->id === $amendment->motionId) {
                continue;
            }
            if (!is_a($otherMotion, Motion::class)) {
                continue;
            }
            foreach ($otherMotion->getVisibleAmendmentsSorted() as $otherAmend) {
                $options[$otherAmend->id] = $otherAmend->getTitle();
            }
        }
        $attrs = ['id' => 'obsoletedByAmendment', 'disabled' => $limitedDisabled];
        echo Html::dropDownList('obsoletedByAmendment', $preObsoletedBy, $options, $attrs);
        ?>
    </section>
    <section class="statusDetails status_<?= Amendment::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION ?>">
        <label class="headingLabel"><?= Yii::t('amend', 'proposal_moved_to_other_motion') ?>:</label>
        <?php
        $options = ['-'];
        $filter = IMotionStatusFilter::onlyUserVisible($consultation, true);
        foreach ($filter->getFilteredConsultationMotions() as $otherMotion) {
            if ($otherMotion->id === $amendment->motionId) {
                continue;
            }
            foreach ($otherMotion->amendments as $otherAmendment) {
                if ($otherAmendment->status === Amendment::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION) {
                    $options[$otherAmendment->id] = $otherMotion->getFormattedTitlePrefix() . ': ' . $otherAmendment->getTitle();
                }
            }
        }
        $attrs = ['id' => 'movedToOtherMotion', 'disabled' => $limitedDisabled];
        echo Html::dropDownList('movedToOtherMotion', $preMovedToMotion, $options, $attrs);
        echo '<div>' . Yii::t('amend', 'proposal_moved_to_other_motion_h') . '</div>';
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
                    if ($amendment->votingStatus == $statusId) {
                        echo 'checked';
                    }
                    ?>> <?= Html::encode($statusName) ?>
                </label><br>
                <?php
            }
            ?>
        </div>
    </section>
    <section class="publicExplanation">
        <h3><?= Yii::t('amend', 'proposal_public_expl_title') ?></h3>
        <?php
        echo Html::textarea(
            'proposalExplanation',
            $proposal->explanation ?: '',
            [
                'title' => Yii::t('amend', 'proposal_public_expl_title'),
                'class' => 'form-control',
                'disabled' => $limitedDisabled,
            ]
        );
        ?>
    </section>
    <section class="collisions <?= (count($collidingAmendments) === 0 ? 'hidden' : '') ?>">
        <h3><?= Yii::t('amend', 'proposal_conflict_title') ?>:</h3>
        <ul>
            <?php
            foreach ($collidingAmendments as $collidingAmendment) {
                $title = $collidingAmendment->getShortTitle();
                $url   = UrlHelper::createAmendmentUrl($collidingAmendment);
                echo '<li class="collision' . $collidingAmendment->id . '">' . Html::a($title, $url);
                if ($collidingAmendment->getLatestProposal()->proposalStatus == Amendment::STATUS_VOTE) {
                    echo ' (' . Yii::t('amend', 'proposal_voting') . ')';
                }
                echo '</li>';
            }
            ?>
        </ul>
    </section>

<?php

echo $this->render('../shared/_proposed_procedure_feedback_form', [
    'imotion' => $amendment,
    'proposal' => $proposal,
    'defaultText' => \app\models\notifications\AmendmentProposedProcedure::getDefaultText($proposal),
]);
echo $this->render('../shared/_proposed_procedure_saving', [
    'imotion' => $amendment,
    'proposal' => $proposal,
    'isLatestVersion' => $isLatestVersion,
]);

if ($context !== 'edit' && $canBeChangedUnlimitedly) {
    $classes   = ['statusDetails'];
    $classes[] = 'status_' . Amendment::STATUS_MODIFIED_ACCEPTED;
    $classes[] = 'status_' . Amendment::STATUS_VOTE;
    ?>
    <section class="<?= implode(' ', $classes) ?>">
        <h3><?= Yii::t('amend', 'proposal_modified_accepted') ?></h3>
        <?php
        echo Html::a(
            Yii::t('base', 'edit'),
            UrlHelper::createAmendmentUrl($amendment, 'edit-proposed-change', ['proposalVersion' => $proposal->version]),
            ['class' => 'editModification']
        );
        ?>
    </section>
    <?php
}
?>
<?= Html::endForm() ?>
