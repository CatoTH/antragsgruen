<?php
use app\components\Tools;
use yii\helpers\Html;

/**
 * @var \app\models\forms\SiteCreateForm $model
 * @var Callable $t
 */

$locale = Tools::getCurrentDateLocale();
$date   = Tools::date2bootstraptime($model->amendmentDeadline);

?>
<div class="step-pane active" id="panelAmendDeadline" data-tab="stepAmendments">
    <fieldset class="amendmentDeadline">
        <legend><?= $t('amenddead_title') ?></legend>
        <div class="description"><?= $t('amenddead_desc') ?></div>
        <div class="options">
            <label class="radio-checkbox-label radio-label value-0">
                <span class="title"><?= $t('amenddead_no') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio(
                        'SiteCreateForm[amendDeadlineExists]',
                        $model->amendmentDeadline === null,
                        ['value' => 0]
                    ); ?>
                </span>
            </label>
            <label class="radio-checkbox-label radio-label broad date-label value-1">
                <span class="title long"><?= $t('amenddead_yes') ?></span>
                <span class="description">
                    <span class="input-group date amendmentDeadline">
                        <input type="text" class="form-control" name="SiteCreateForm[amendDeadline]"
                               value="<?= Html::encode($date) ?>" data-locale="<?= Html::encode($locale) ?>">
                        <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </span>
                <span class="input">
                    <?= Html::radio(
                        'SiteCreateForm[amendDeadlineExists]',
                        $model->amendmentDeadline !== null,
                        ['value' => 1, 'class' => 'amendDeadlineExists']
                    ); ?>
                </span>
            </label>
        </div>
    </fieldset>
    <div class="navigation">
        <button class="btn btn-lg btn-prev">
            <span class="icon-chevron-left" aria-hidden="true"></span>
            <?= $t('prev') ?>
        </button>
        <button class="btn btn-lg btn-next btn-primary">
            <span class="icon-chevron-right" aria-hidden="true"></span>
            <?= $t('next') ?>
        </button>
    </div>
</div>
