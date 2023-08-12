<?php

use app\components\UrlHelper;
use app\plugins\dbwv\workflow\Workflow;
use app\models\db\{ConsultationSettingsTag, Motion};
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/step7-publish-resolution', 'motionSlug' => $motion->getMotionSlug()]);

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_step7_publish_resolution',
    'class' => 'dbwv_step dbwv_step7_publish_resolution',
]);

$titlePrefix = $motion->getMyConsultation()->getNextMotionPrefix($motion->motionTypeId, [], 'B/');

?>
    <h2>Veröffentlichung des Beschlusses</h2>
    <div class="holder">
        <div>
            <div style="padding: 10px; clear:both;">
                <label for="dbwv_step7_prefix" style="display: inline-block; width: 200px; padding-top: 7px;">
                    Beschlussnummer:
                </label>
                <div style="display: inline-block; width: 400px; padding-top: 7px;">
                    <input type="text" value="<?= Html::encode($titlePrefix) ?>" name="motionPrefix" class="form-control" id="dbwv_step7_prefix">
                </div>
                <br>
            </div>
            <div style="text-align: right;">
                <button type="submit" class="btn btn-primary">
                    <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                    Beschluss erstellen
                </button>
            </div>
        </div>
    </div>
<?php
echo Html::endForm();
