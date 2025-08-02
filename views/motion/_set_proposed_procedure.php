<?php

/**
 * @var Yii\web\View $this
 * @var Motion $motion
 * @var MotionProposal $proposal
 * @var string $context
 * @var string $saveUrl
 */

use app\components\{IMotionStatusFilter, UrlHelper};
use app\models\db\{Motion, MotionProposal};
use yii\helpers\Html;

$saveUrl = UrlHelper::createMotionUrl($motion, 'save-proposal-status');
echo Html::beginForm($saveUrl, 'POST', [
    'id'                       => 'proposedChanges',
    'class'                    => 'version' . $motion->version,
    'data-antragsgruen-widget' => 'backend/ChangeProposedProcedure',
    'data-context'             => $context,
    'data-proposal-id'         => ($proposal->isNewRecord ? null : $proposal->id),
]);
if ($proposal->proposalStatus === Motion::STATUS_REFERRED) {
    $preReferredTo = $proposal->comment;
} else {
    $preReferredTo = '';
}
if ($proposal->proposalStatus === Motion::STATUS_OBSOLETED_BY_AMENDMENT) {
    $preObsoletedBy = $proposal->comment;
} else {
    $preObsoletedBy = '';
}
if ($proposal->proposalStatus === Motion::STATUS_CUSTOM_STRING) {
    $preCustomStr = $proposal->comment;
} else {
    $preCustomStr = '';
}

if (isset($msgAlert)) {
    echo '<div class="alert alert-info">' . $msgAlert . '</div>';
}

$consultation = $motion->getMyConsultation();
$votingBlocks = $consultation->votingBlocks;
$currBlockIsLocked = ($motion->votingBlock && !$motion->votingBlock->itemsCanBeRemoved());
$canBeChangedUnlimitedly = $proposal->canEditProposedProcedure();
$limitedDisabled = ($canBeChangedUnlimitedly ? null : true);
$voting = $motion->getVotingData();
?>
<h2>
    <?= Yii::t('amend', 'proposal_amend_title') ?>
    <button class="pull-right btn-link closeBtn" type="button"
            title="<?= Html::encode(Yii::t('amend', 'proposal_close')) ?>">
        <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
        <span class="sr-only"><?= Html::encode(Yii::t('amend', 'proposal_close')) ?></span>
    </button>
</h2>
<?php
if (count($motion->proposals) > 1) {
    ?>
    <section class="proposalHistory">
        <div class="versionList">
            <ol>
                <?php
                foreach ($motion->proposals as $itProp) {
                    $versionName = str_replace('%VERSION%', $itProp->version, Yii::t('amend', 'proposal_version_x'));
                    if ($itProp->id === $proposal->id) {
                        echo '<li>' . Html::encode($versionName) . '</li>';
                    } else {
                        $versionLink = UrlHelper::createMotionUrl($motion, 'view', ['proposalVersion' => $itProp->id]);
                        echo '<li>' . Html::a(Html::encode($versionName), $versionLink) . '</li>';
                    }
                }
                ?>
            </ol>
        </div>
    </section>
    <?php
}
?>
<div class="holder">
    <fieldset class="statusForm">
        <legend class="hidden"><?= Yii::t('amend', 'proposal_status_title') ?></legend>
        <h3><?= Yii::t('amend', 'proposal_status_title') ?></h3>

        <?php
        $foundStatus = false;
        foreach ($consultation->getStatuses()->getMotionProposedProcedureStatuses() as $statusId => $statusName) {
            ?>
            <label class="proposalStatus<?= $statusId ?>">
                <input type="radio" name="proposalStatus" value="<?= $statusId ?>"<?php
                if (intval($proposal->proposalStatus) === intval($statusId)) {
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
    </fieldset>
    <div class="middleCol">
        <fieldset class="visibilitySettings showIfStatusSet">
            <legend class="hidden"><?= Yii::t('amend', 'proposal_publicity') ?></legend>
            <h3><?= Yii::t('amend', 'proposal_publicity') ?></h3>
            <label>
                <?= Html::checkbox('proposalVisible', ($proposal->visibleFrom !== null), ['disabled' => $limitedDisabled]) ?>
                <?= Yii::t('amend', 'proposal_visible') ?>
            </label>
            <label>
                <?= Html::checkbox('setPublicExplanation', ($proposal->explanation !== null), ['disabled' => $limitedDisabled]) ?>
                <?= Yii::t('amend', 'proposal_public_expl_set') ?>
            </label>
        </fieldset>

        <?= $this->render('../shared/_proposed_procedure_votings', ['imotion' => $motion]) ?>

        <?= $this->render('../shared/_proposed_procedure_feedback_status', ['imotion' => $motion, 'proposal' => $proposal]) ?>
    </div>
    <section class="proposalCommentForm">
        <h3><?= Yii::t('amend', 'proposal_comment_title') ?></h3>

        <?= $this->render('../shared/_proposed_procedure_log', ['imotion' => $motion]) ?>
    </section>
</div>

<?= $this->render('../shared/_proposed_procedure_tags', ['imotion' => $motion]) ?>

<section class="statusDetails status_<?= Motion::STATUS_OBSOLETED_BY_AMENDMENT ?>">
    <label class="headingLabel"><?= Yii::t('amend', 'proposal_obsoleted_by') ?>...</label>
    <?php
    $options = ['-'];
    $filter = IMotionStatusFilter::onlyUserVisible($consultation, false);
    foreach ($filter->getFilteredConsultationIMotionsSorted() as $otherMotion) {
        if ($otherMotion->id === $motion->id) {
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
    echo Html::dropDownList('obsoletedByMotion', $preObsoletedBy, $options, $attrs);
    ?>
</section>
<section class="statusDetails status_<?= Motion::STATUS_REFERRED ?>">
    <label class="headingLabel" for="referredTo"><?= Yii::t('amend', 'proposal_refer_to') ?>...</label>
    <input type="text" name="referredTo" id="referredTo" value="<?= Html::encode($preReferredTo) ?>"
        <?php if (!$canBeChangedUnlimitedly) echo 'disabled'; ?>
           class="form-control">
</section>
<section class="statusDetails status_<?= Motion::STATUS_CUSTOM_STRING ?>">
    <label class="headingLabel" for="statusCustomStr"><?= Yii::t('amend', 'proposal_custom_str') ?>:</label>
    <input type="text" name="statusCustomStr" id="statusCustomStr" value="<?= Html::encode($preCustomStr) ?>"
        <?php if (!$canBeChangedUnlimitedly) echo 'disabled'; ?>
           class="form-control">
</section>
<section class="statusDetails status_<?= Motion::STATUS_VOTE ?>">
    <div class="votingStatus">
        <h3><?= Yii::t('amend', 'proposal_voting_status') ?></h3>
        <?php
        foreach ($consultation->getStatuses()->getVotingStatuses() as $statusId => $statusName) {
            ?>
            <label>
                <input type="radio" name="votingStatus" value="<?= $statusId ?>" <?php
                if ($motion->votingStatus == $statusId) {
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
<?php

echo $this->render('../shared/_proposed_procedure_feedback_form', [
    'imotion' => $motion,
    'proposal' => $proposal,
    'defaultText' => \app\models\notifications\MotionProposedProcedure::getDefaultText($proposal),
]);
echo $this->render('../shared/_proposed_procedure_saving', ['imotion' => $motion, 'proposal' => $proposal]);

if ($context !== 'edit' && $canBeChangedUnlimitedly) {
    $classes   = ['statusDetails'];
    $classes[] = 'status_' . Motion::STATUS_MODIFIED_ACCEPTED;
    $classes[] = 'status_' . Motion::STATUS_VOTE;
    ?>
    <section class="<?= implode(' ', $classes) ?>">
        <h3><?= Yii::t('amend', 'proposal_modified_accepted') ?></h3>
        <?php
        echo Html::a(
            Yii::t('base', 'edit'),
            UrlHelper::createMotionUrl($motion, 'edit-proposed-change'),
            ['class' => 'editModification']
        );
        ?>
    </section>
    <?php
}
?>
<?= Html::endForm() ?>
