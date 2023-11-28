<?php

use app\plugins\member_petitions\ConsultationSettings;
use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Consultation $consultation
 */

/** @var ConsultationSettings $settings */
$settings = $consultation->getSettings();

?>
<h2 class="green"><?= Yii::t('member_petitions', 'sett_title') ?></h2>
<section class="content">
    <div>
        <label>
            <?= Html::checkbox('settings[petitionPage]', $settings->petitionPage) ?>
            <?= Yii::t('member_petitions', 'sett_petitions_active') ?>
            <input type="hidden" name="settingsFields[]" value="petitionPage">
        </label>
    </div>
    <div>
        <label>
            <?= Html::checkbox('settings[canAlwaysRespond]', $settings->canAlwaysRespond) ?>
            <?= Yii::t('member_petitions', 'sett_can_always_respond') ?>
            <input type="hidden" name="settingsFields[]" value="canAlwaysRespond">
        </label>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label"
               for="consultationOrgaId"><?= Yii::t('member_petitions', 'sett_orgaid') ?>:</label>
        <div class="col-sm-9">
            <input type="text" required name="settings[organizationId]"
                   value="<?= Html::encode($settings->organizationId) ?>"
                   class="form-control" id="consultationOrgaId">
            <input type="hidden" name="settingsFields[]" value="organizationId">
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label"
               for="minDiscussionTimeId"><?= Yii::t('member_petitions', 'sett_minDiscussionTime') ?>:</label>
        <div class="col-sm-9">
            <input type="number" required name="settings[minDiscussionTime]"
                   value="<?= Html::encode($settings->minDiscussionTime) ?>"
                   class="form-control" id="minDiscussionTimeId">
            <input type="hidden" name="settingsFields[]" value="minDiscussionTime">
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label"
               for="maxOverallTime"><?= Yii::t('member_petitions', 'sett_maxOverallTime') ?>:<br>
            <small><?= Yii::t('member_petitions', 'sett_maxOverallTimeD') ?></small>
        </label>
        <div class="col-sm-9">
            <input type="number" required name="settings[maxOverallTime]"
                   value="<?= Html::encode($settings->maxOverallTime) ?>"
                   class="form-control" id="maxOverallTime">
            <input type="hidden" name="settingsFields[]" value="maxOverallTime">
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label"
               for="replyDeadlineId"><?= Yii::t('member_petitions', 'sett_replydeadline') ?>:</label>
        <div class="col-sm-9">
            <input type="number" required name="settings[replyDeadline]"
                   value="<?= Html::encode($settings->replyDeadline) ?>"
                   class="form-control" id="replyDeadlineId">
            <input type="hidden" name="settingsFields[]" value="replyDeadline">
        </div>
    </div>
</section>
