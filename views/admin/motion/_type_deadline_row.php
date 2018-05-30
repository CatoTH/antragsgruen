<?php

use app\components\Tools;
use yii\helpers\Html;

/**
 * @var string $locale
 * @var string $type
 * @var array $data
 */

$start = Tools::dateSql2bootstraptime($data['start']);
$end = Tools::dateSql2bootstraptime($data['end']);

?>
<div class="row deadlineEntry">
    <div class="col-md-4 col">
        <div class="input-group date datetimepickerFrom">
            <span class="input-group-addon">
                <?= \Yii::t('admin', 'motion_deadline_from') ?>
            </span>
            <input type="text" class="form-control"
                   name="deadlines[<?= $type ?>][start][]"
                   value="<?= Html::encode($start) ?>"
                   placeholder="<?= \Yii::t('admin', 'motion_deadline_from_p') ?>"
                   data-locale="<?= Html::encode($locale) ?>">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
        </div>
    </div>
    <div class="col-md-4 col">
        <div class="input-group date datetimepickerTo">
            <span class="input-group-addon">
                <?= \Yii::t('admin', 'motion_deadline_to') ?>
            </span>
            <input type="text" class="form-control"
                   name="deadlines[<?= $type ?>][end][]"
                   value="<?= Html::encode($end) ?>"
                   placeholder="<?= \Yii::t('admin', 'motion_deadline_to_p') ?>"
                   data-locale="<?= Html::encode($locale) ?>">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
        </div>
    </div>
    <div class="col-md-3 col">
        <input type="text" class="form-control phaseTitle" placeholder="<?= \Yii::t('admin', 'motion_deadline_name') ?>"
               name="deadlines[<?= $type ?>][title][]"
               value="<?= Html::encode($data['title'] ? $data['title'] : '') ?>">
    </div>
    <div class="col-md-1">
        <button type="button" class="btn btn-link btn-danger delRow">
            <span class="glyphicon glyphicon-remove-circle"></span>
        </button>
    </div>
</div>
