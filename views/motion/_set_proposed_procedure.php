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

$isLatestVersion = ($proposal->id === $motion->getLatestProposal()->id);
$saveUrl = UrlHelper::createMotionUrl($motion, 'save-proposal-status');
echo Html::beginForm($saveUrl, 'POST', [
    'id'                       => 'proposedChanges',
    'class'                    => 'version' . $motion->version . ($isLatestVersion ? ' latestVersion' : ' oldVersion'),
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

<?= $this->render('../shared/_proposed_procedure_versions', ['imotion' => $motion, 'proposal' => $proposal]) ?>

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
    <?= $this->render('../shared/_proposed_procedure_log', ['imotion' => $motion]) ?>
</div>

<?= $this->render('../shared/_proposed_procedure_tags', ['imotion' => $motion]) ?>

<?= $this->render('../shared/_proposed_procedure_status_details', [
    'imotion' => $motion,
    'proposal' => $proposal,
    'limitedDisabled' => $limitedDisabled,
    'canBeChangedUnlimitedly' => $canBeChangedUnlimitedly,
]) ?>

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
echo $this->render('../shared/_proposed_procedure_saving', [
    'imotion' => $motion,
    'proposal' => $proposal,
    'isLatestVersion' => $isLatestVersion,
]);

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
            UrlHelper::createMotionUrl($motion, 'edit-proposed-change', ['proposalVersion' => $proposal->version]),
            ['class' => 'editModification']
        );
        ?>
    </section>
    <?php
}
?>
<?= Html::endForm() ?>
