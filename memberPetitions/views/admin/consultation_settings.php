<?php

use \app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Consultation $consultation
 */

/** @var \app\models\settings\ConsultationSettings $settings */
$settings = $consultation->getSettings();

?>
<h2 class="green">Mitgliederbegehren</h2>
<section class="content">
    <div class="form-group">
        <label class="col-sm-3 control-label"
               for="consultationOrgaId">Organisations-ID:</label>
        <div class="col-sm-9">
            <input type="text" required name="settings[organizationId]"
                   value="<?= Html::encode($settings->organizationId) ?>"
                   class="form-control" id="consultationOrgaId">
            <input type="hidden" name="settingsFields[]" value="organizationId">
        </div>
    </div>
</section>
