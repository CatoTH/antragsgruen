<?php
use app\components\Tools;
use app\models\forms\SiteCreateForm2;
use yii\helpers\Html;

/**
 * @var SiteCreateForm2 $model
 * @var \Callable $t
 */

$locale = Tools::getCurrentDateLocale();
$date   = Tools::dateSql2bootstraptime($model->amendmentDeadline);

?>
<div class="step-pane active" id="panelAmendDeadline" data-tab="stepAmendment">
    <fieldset class="amendmentDeadline">
        <legend><?= $t('amenddead_title') ?></legend>
        <div class="description"><?= $t('amenddead_desc') ?></div>
        <div class="options">
            <label class="radio-label">
                <span class="title"><?= $t('amenddead_no') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio(
                        'SiteCreateForm2[amendDeadlineExists]',
                        $model->amendmentDeadline === null,
                        ['value' => 0]
                    ); ?>
                </span>
            </label>
            <label class="radio-label broad date-label">
                <span class="title long"><?= $t('amenddead_yes') ?></span>
                <span class="description">
                    <span class="input-group date amendmentDeadline">
                        <input type="text" class="form-control" name="SiteCreateForm2[amendmentDeadline]"
                               value="<?= Html::encode($date) ?>" data-locale="<?= Html::encode($locale) ?>">
                        <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </span>
                <span class="input">
                    <?= Html::radio(
                        'SiteCreateForm2[amendDeadlineExists]',
                        $model->amendmentDeadline !== null,
                        ['value' => 1, 'class' => 'amendDeadlineExists']
                    ); ?>
                </span>
            </label>
        </div>
    </fieldset>
    <div class="navigation">
        <button class="btn btn-lg btn-prev"><span class="icon-chevron-left"></span> <?= $t('prev') ?></button>
        <button class="btn btn-lg btn-next btn-primary"><span class="icon-chevron-right"></span> <?= $t('next') ?>
        </button>
    </div>
</div>
