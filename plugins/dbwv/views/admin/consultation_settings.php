<?php

use app\plugins\dbwv\ConsultationSettings;
use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Consultation $consultation
 */

/** @var ConsultationSettings $settings */
$settings = $consultation->getSettings();

?>
<h2 class="green">Deutscher BundeswehrVerband</h2>
<section class="content">
    <div class="form-group">
        <label class="col-sm-3 control-label" for="defaultVersionFilter">Standard-Version in Antragsliste:</label>
        <div class="col-sm-9">
            <input type="text" name="settings[defaultVersionFilter]"
                   value="<?= Html::encode($settings->defaultVersionFilter) ?>"
                   class="form-control" id="defaultVersionFilter">
            <input type="hidden" name="settingsFields[]" value="defaultVersionFilter">
        </div>
    </div>
</section>
