<?php
use app\components\HTMLTools;
use app\components\UrlHelper;
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
                echo \Yii::t('amend', 'merge1_introduction');
            } else {
                echo \Yii::t('amend', 'merge1_introduction_user');
            }
            ?>
        </div>

        <div class="form-group">
            <label for="motionTitlePrefix"><?= \Yii::t('amend', 'merge1_motion_prefix') ?></label>
            <input type="text" class="form-control" id="motionTitlePrefix" name="motionTitlePrefix"
                   value="<?= Html::encode($amendment->getMyMotion()->getNewTitlePrefix()) ?>">
        </div>

        <div class="form-group">
            <label for="amendmentStatus"><?= \Yii::t('amend', 'merge1_amend_status') ?></label>
            <div class="fueluxSelectHolder">
                <?php
                echo HTMLTools::fueluxSelectbox(
                    'amendmentStatus',
                    Amendment::getStati(),
                    Amendment::STATUS_ACCEPTED,
                    ['id' => 'amendmentStatus']
                );
                ?>
            </div>
        </div>
    </div>
    <?php if ($allowStatusChanging) { ?>
    <fieldset class="otherAmendmentStatus">
        <h2 class="green"><?= \Yii::t('amend', 'merge1_other_status') ?></h2>
        <div class="content">
            <div class="alert alert-info"><?= \Yii::t('amend', 'merge1_status_intro') ?></div>

            <?php
            foreach ($otherAmendments as $otherAmend) {
                echo '<div class="row"><div class="col-md-5">';
                echo HTMLTools::amendmentDiffTooltip($otherAmend, 'bottom');
                echo Html::a($otherAmend->getTitle(), UrlHelper::createAmendmentUrl($otherAmend));
                echo '<span class="by">' . \Yii::t('amend', 'merge1_amend_by') . ': ' .
                    $otherAmend->getInitiatorsStr() . '</span>';
                echo '</div><div class="col-md-7"><div class="fueluxSelectHolder">';
                $statiAll = $amendment->getStati();
                $stati    = [];
                foreach (Amendment::getStatiMarkAsDoneOnRewriting() as $statusId) {
                    $stati[$statusId] = $statiAll[$statusId];
                }
                $stati[$otherAmend->status] = \Yii::t('amend', 'merge1_status_unchanged') . ': ' .
                    $statiAll[$amendment->status];
                $statusPre = ($amendment->globalAlternative ? Amendment::STATUS_REJECTED : $otherAmend->status);
                echo HTMLTools::fueluxSelectbox(
                    'otherAmendmentsStatus[' . $otherAmend->id . ']',
                    $stati,
                    $statusPre,
                    ['data-amendment-id' => $otherAmend->id, 'id' => 'otherAmendmentsStatus' . $otherAmend->id]
                );
                echo '</div></div></div>';
            }
            ?>
        </div>
    </fieldset>
    <?php } ?>

    <div class="content save-row">
        <button class="goto_2 btn btn-primary">
            <?= \Yii::t('amend', 'merge1_goon') ?>
        </button>
    </div>
</div>