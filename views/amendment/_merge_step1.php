<?php
use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var Amendment $amendment
 */

?>
<div class="step_1">
    <div class="content">
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

    <fieldset class="otherAmendmentStatus">
        <h2 class="green"><?= \Yii::t('amend', 'merge1_other_status') ?></h2>
        <div class="content">
            <?php
            foreach ($otherAmendments as $otherAmend) {
                echo '<div class="row"><div class="col-md-3">';
                echo Html::a($otherAmend->getTitle(), UrlHelper::createAmendmentUrl($otherAmend));
                echo ' (' . \Yii::t('amend', 'merge1_amend_by') . ': ' . $otherAmend->getInitiatorsStr() . ')';
                echo '</div><div class="col-md-9"><div class="fueluxSelectHolder">';
                $statiAll                   = $amendment->getStati();
                $stati                      = [
                    Amendment::STATUS_ACCEPTED          => $statiAll[Amendment::STATUS_ACCEPTED],
                    Amendment::STATUS_REJECTED          => $statiAll[Amendment::STATUS_REJECTED],
                    Amendment::STATUS_MODIFIED_ACCEPTED => $statiAll[Amendment::STATUS_MODIFIED_ACCEPTED],
                ];
                $stati[$otherAmend->status] = \Yii::t('amend', 'merge1_status_unchanged') . ': ' .
                    $statiAll[$amendment->status];
                echo HTMLTools::fueluxSelectbox(
                    'otherAmendmentsStatus[' . $otherAmend->id . ']',
                    $stati,
                    $otherAmend->status
                );
                echo '</div></div></div>';
            }
            ?>
        </div>
    </fieldset>

    <div class="content save-row">
        <button class="goto_2 btn btn-primary">
            <?= \Yii::t('amend', 'merge1_goon') ?>
        </button>
    </div>
</div>