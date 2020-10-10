<?php

use app\plugins\egp\ConsultationSettings;
use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Consultation $consultation
 */

/** @var ConsultationSettings $settings */
$settings = $consultation->getSettings();

?>
<h2 class="green">EGP Settings</h2>
<section class="content">
    <div class="form-group">
        <label class="col-sm-3 control-label"
               for="homeRedirectUrlId">Home URL:</label>
        <div class="col-sm-9">
            <input type="text" name="settings[homeRedirectUrl]"
                   value="<?= Html::encode($settings->homeRedirectUrl) ?>"
                   class="form-control" id="homeRedirectUrlId">
            <input type="hidden" name="settingsFields[]" value="homeRedirectUrl">
        </div>
    </div>
</section>
