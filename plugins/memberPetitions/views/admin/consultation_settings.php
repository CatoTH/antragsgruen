<?php

use app\plugins\memberPetitions\ConsultationSettings;
use \app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Consultation $consultation
 */

/** @var ConsultationSettings $settings */
$settings = $consultation->getSettings();

?>
<h2 class="green"><?= \Yii::t('memberpetitions', 'sett_title') ?></h2>
<section class="content">
    <div class="form-group">
        <label class="col-sm-3 control-label"
               for="consultationOrgaId"><?= \Yii::t('memberpetitions', 'sett_orgaid') ?>:</label>
        <div class="col-sm-9">
            <input type="text" required name="settings[organizationId]"
                   value="<?= Html::encode($settings->organizationId) ?>"
                   class="form-control" id="consultationOrgaId">
            <input type="hidden" name="settingsFields[]" value="organizationId">
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label"
               for="minDiscussionTimeId"><?= \Yii::t('memberpetitions', 'sett_minDiscussionTime') ?>:</label>
        <div class="col-sm-9">
            <input type="number" required name="settings[minDiscussionTime]"
                   value="<?= Html::encode($settings->minDiscussionTime) ?>"
                   class="form-control" id="minDiscussionTimeId">
            <input type="hidden" name="settingsFields[]" value="minDiscussionTime">
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label"
               for="replyDeadlineId"><?= \Yii::t('memberpetitions', 'sett_replydeadline') ?>:</label>
        <div class="col-sm-9">
            <input type="number" required name="settings[replyDeadline]"
                   value="<?= Html::encode($settings->replyDeadline) ?>"
                   class="form-control" id="replyDeadlineId">
            <input type="hidden" name="settingsFields[]" value="replyDeadline">
        </div>
    </div>
</section>
