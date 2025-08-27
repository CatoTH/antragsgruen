<?php

/**
 * @var Yii\web\View $this
 * @var Amendment $amendment
 * @var AmendmentProposal $proposal
 * @var string $context
 */

use app\components\{IMotionStatusFilter, UrlHelper};
use app\models\db\{Amendment, AmendmentProposal};
use yii\helpers\Html;

$collidingAmendments = $proposal->collidesWithOtherProposedAmendments();

$saveUrl = UrlHelper::createAmendmentUrl($amendment, 'save-proposal-status');
$isLatestVersion = ($proposal->id === $amendment->getLatestProposal()->id);
$classes = [($isLatestVersion ? 'latestVersion' : 'oldVersion')];
if ($proposal->isNewRecord) {
    $classes[] = 'new';
}
echo Html::beginForm($saveUrl, 'POST', [
    'id'                       => 'proposedChanges',
    'class'                    => implode(' ', $classes),
    'data-antragsgruen-widget' => 'backend/ChangeProposedProcedure',
    'data-context'             => $context,
    'data-proposal-id'         => ($proposal->isNewRecord ? null : $proposal->id),
]);
if ($proposal->proposalStatus === Amendment::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION) {
    $preMovedToMotion = $proposal->comment;
} else {
    $preMovedToMotion = '';
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

    <?= $this->render('../shared/_proposed_procedure_status_details', [
        'imotion' => $amendment,
        'proposal' => $proposal,
        'limitedDisabled' => $limitedDisabled,
        'canBeChangedUnlimitedly' => $canBeChangedUnlimitedly,
    ]) ?>

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
                $urlTitle = AmendmentProposal::getAmendmentTitleUrlConsideringProposals($collidingAmendment);
                echo '<li class="collision' . $collidingAmendment->id . '">' . Html::a(Html::encode($urlTitle['title']), $urlTitle['url']);
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
    $classes   = ['statusDetails', 'statusModifiedLink'];
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
