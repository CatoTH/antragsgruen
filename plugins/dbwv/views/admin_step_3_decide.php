<?php

use app\components\UrlHelper;
use app\models\db\{IMotion, Motion};
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/step3decide', 'motionSlug' => $motion->getMotionSlug()]);

if ($motion->version === \app\plugins\dbwv\workflow\Workflow::STEP_NAME_V4) {
    $decision = $motion->status;
} else {
    $decision = null;
}

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_step3_decide',
    'class' => 'dbwv_step dbwv_step3_decide',
]);
?>
    <h2>V3 - Administration <small>(Redaktionsausschuss)</small></h2>
    <div class="holder">
        <div style="padding: 10px;">
            <label>
                <?= Html::radio('decision', $decision === IMotion::STATUS_ACCEPTED, ['value' => IMotion::STATUS_ACCEPTED, 'required' => 'required']) ?>
                <?= Yii::t('structure', 'STATUS_ACCEPTED') ?>
            </label><br>
            <label>
                <?= Html::radio('decision', $decision === IMotion::STATUS_MODIFIED_ACCEPTED, ['value' => IMotion::STATUS_MODIFIED_ACCEPTED, 'required' => 'required']) ?>
                <?= Yii::t('structure', 'STATUS_MODIFIED_ACCEPTED') ?>
            </label><br>
            <label>
                <?= Html::radio('decision', $decision === IMotion::STATUS_REJECTED, ['value' => IMotion::STATUS_REJECTED, 'required' => 'required']) ?>
                <?= Yii::t('structure', 'STATUS_REJECTED') ?>
            </label><br>
            <label>
                <?= Html::radio('decision', $decision === IMotion::STATUS_CUSTOM_STRING, ['value' => IMotion::STATUS_CUSTOM_STRING, 'required' => 'required']) ?>
                <?= Yii::t('structure', 'STATUS_CUSTOM_STRING') ?>
            </label><br>
            <label id="dbwv_step3_custom_str" class="hidden">
                <input type="text" class="form-control" name="custom_string" value="<?= Html::encode($motion->proposalComment) ?>">
            </label>
        </div>
        <div style="text-align: right;">
            <button type="submit" class="btn btn-primary">
                <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                V4 Beschluss festlegen
            </button>
        </div>
    </div>
    <script>
        $(function() {
            $("#dbwv_step3_decide input[name=decision]").on("change", function() {
                if ($("#dbwv_step3_decide input[name=decision]:checked").val() == <?= IMotion::STATUS_CUSTOM_STRING ?>) {
                    $("#dbwv_step3_custom_str").removeClass('hidden');
                } else {
                    $("#dbwv_step3_custom_str").addClass('hidden');
                }
            }).trigger("change");
        });
    </script>
<?php
echo Html::endForm();
