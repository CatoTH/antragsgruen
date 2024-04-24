<?php

use app\components\{HTMLTools, MotionNumbering, UrlHelper};
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var Amendment $amendment
 * @var bool $allowStatusChanging
 * @var Amendment[] $otherAmendments
 */

?>
<div class="step_1">
    <div class="content">
        <div class="alert alert-info">
            <?php
            if ($allowStatusChanging) {
                echo Yii::t('amend', 'merge1_introduction');
            } else {
                echo Yii::t('amend', 'merge1_introduction_user');
            }
            ?>
        </div>

        <div class="form-group">
            <label for="motionTitlePrefix"><?= Yii::t('amend', 'merge1_motion_prefix') ?></label>
            <input type="text" class="form-control" id="motionTitlePrefix" name="motionTitlePrefix"
                   value="<?= Html::encode($amendment->getMyMotion()->titlePrefix) ?>">
        </div>

        <div class="form-group">
            <label for="motionVersion"><?= Yii::t('amend', 'merge1_motion_version') ?></label>
            <input type="text" class="form-control" id="motionVersion" name="motionVersion"
                   value="<?= Html::encode(MotionNumbering::getNewVersion($amendment->getMyMotion()->version)) ?>">
        </div>

        <div class="form-group">
            <label for="amendmentStatus"><?= Yii::t('amend', 'merge1_amend_status') ?></label>
            <div class="fueluxSelectHolder">
                <?php
                echo Html::dropDownList(
                    'amendmentStatus',
                    Amendment::STATUS_ACCEPTED,
                    $amendment->getMyConsultation()->getStatuses()->getStatusNames(),
                    ['id' => 'amendmentStatus', 'class' => 'stdDropdown']
                );
                ?>
            </div>
        </div>
    </div>
    <?php if ($allowStatusChanging) { ?>
    <fieldset class="otherAmendmentStatus">
        <h2 class="green"><?= Yii::t('amend', 'merge1_other_status') ?></h2>
        <div class="content">
            <div class="alert alert-info"><?= Yii::t('amend', 'merge1_status_intro') ?></div>

            <?php
            foreach ($otherAmendments as $otherAmend) {
                echo '<div class="stdTwoCols"><div class="leftColumnUnstyled">';
                echo HTMLTools::amendmentDiffTooltip($otherAmend, 'bottom');
                echo Html::a(Html::encode($otherAmend->getTitle()), UrlHelper::createAmendmentUrl($otherAmend));
                echo '<span class="by">' . Yii::t('amend', 'merge1_amend_by') . ': ' .
                    $otherAmend->getInitiatorsStr() . '</span>';
                echo '</div><div class="rightColumn">';
                $statusesAll = $amendment->getMyConsultation()->getStatuses()->getStatusNames();
                $statuses    = [];
                foreach ($amendment->getMyConsultation()->getStatuses()->getStatusesMarkAsDoneOnRewriting() as $statusId) {
                    $statuses[$statusId] = $statusesAll[$statusId];
                }
                $statuses[$otherAmend->status] = Yii::t('amend', 'merge1_status_unchanged') . ': ' .
                    $statusesAll[$amendment->status];
                $statusPre = ($amendment->globalAlternative ? Amendment::STATUS_REJECTED : $otherAmend->status);
                echo Html::dropDownList(
                    'otherAmendmentsStatus[' . $otherAmend->id . ']',
                    $statusPre,
                    $statuses,
                    ['data-amendment-id' => $otherAmend->id, 'id' => 'otherAmendmentsStatus' . $otherAmend->id, 'class' => 'stdDropdown']
                );
                echo '</div></div>';
            }
            ?>
        </div>
    </fieldset>
    <?php } ?>

    <div class="content save-row">
        <button class="goto_2 btn btn-primary">
            <?= Yii::t('amend', 'merge1_goon') ?>
        </button>
    </div>
</div>
