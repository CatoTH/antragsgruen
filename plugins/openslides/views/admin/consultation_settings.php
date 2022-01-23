<?php

use app\plugins\openslides\{ConsultationSettings, SiteSettings};
use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Consultation $consultation
 */

/** @var ConsultationSettings $settings */
$settings = $consultation->getSettings();
/** @var SiteSettings $siteSettings */
$siteSettings = $consultation->site->getSettings();

?>
<h2 class="green"><?= Yii::t('openslides', 'sett_title') ?></h2>
<section class="content">
    <div class="adminTwoCols">
        <label class="leftColumn" for="siteOsBaseUri"><?= Yii::t('openslides', 'sett_osbaseuri') ?>:</label>
        <div class="rightColumn">
            <input type="text" required name="siteSettings[osBaseUri]"
                   value="<?= Html::encode($siteSettings->osBaseUri) ?>"
                   class="form-control" id="siteOsBaseUri">
            <input type="hidden" name="siteSettingsFields[]" value="osBaseUri">
        </div>
    </div>
    <div class="adminTwoCols">
        <label class="leftColumn" for="siteOsApiKey"><?= Yii::t('openslides', 'sett_apikey') ?>:</label>
        <div class="rightColumn">
            <input type="text" required name="siteSettings[osApiKey]"
                   value="<?= Html::encode($siteSettings->osApiKey) ?>"
                   class="form-control" id="siteOsApiKey">
            <input type="hidden" name="siteSettingsFields[]" value="osApiKey">
        </div>
    </div>
</section>
