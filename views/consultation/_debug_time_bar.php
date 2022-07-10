<?php

use app\components\{DateTools, Tools, UrlHelper};
use Yii\helpers\Html;

/**
 * @var \app\models\db\Consultation $consultation
 */

$simulatedTime = DateTools::getSimulatedTime($consultation);
$locale        = Tools::getCurrentDateLocale();

echo Html::beginForm(UrlHelper::createUrl('consultation/debugbar-ajax'), 'post', [
    'class'                    => 'stickyAdminDebugFooter',
    'data-antragsgruen-widget' => 'frontend/DeadlineDebugBar',
]);
?>
<h2 class="headCol">
    <label for="simulateAdminTimeInput"><?= Yii::t('base', 'Zeitpunkt simulieren') ?></label>
    <span class="adminHint">(<?= Yii::t('base', 'debug_deadline_hint') ?>)</span>
</h2>
<div class="setterCol">
    <div class="input-group date" id="simulateAdminTime">
        <input type="text" class="form-control" name="simulatedTime" id="simulateAdminTimeInput"
               value="<?= Tools::dateSql2bootstraptime($simulatedTime) ?>" data-locale="<?= Html::encode($locale) ?>">
        <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
    </div>
    <button type="button" class="btn btn-default setTime">
        <?= Yii::t('base', 'debug_deadline_set') ?>
    </button>
</div>
<div class="closeCol">
    <button class="btn btn-sm btn-danger" type="button">
        <?= Yii::t('base', 'debug_deadline_quit') ?>
    </button>
</div>
<?= Html::endForm() ?>
