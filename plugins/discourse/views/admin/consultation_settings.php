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
<h2 class="green"><?= Yii::t('discourse', 'sett_title') ?></h2>
<section class="content">
    <div class="form-group">
        <label class="col-sm-3 control-label"
               for="discourseCategoryId"><?= Yii::t('discourse', 'sett_discourse_cat') ?>:</label>
        <div class="col-sm-9">
            <input type="text" name="settings[discourseCategoryId]"
                   value="<?= Html::encode($settings->discourseCategoryId) ?>"
                   class="form-control" id="discourseCategoryId">
            <input type="hidden" name="settingsFields[]" value="discourseCategoryId">
        </div>
    </div>
</section>
