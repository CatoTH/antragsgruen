<?php
use app\components\Tools;
use app\models\forms\SiteCreateForm2;
use yii\helpers\Html;

/**
 * @var SiteCreateForm2 $model
 * @var \Callable $t
 */

$locale = Tools::getCurrentDateLocale();
$date   = Tools::dateSql2bootstraptime($model->motionDeadline);

?>
<div class="step-pane active" id="panelMotionDeadline" data-tab="stepMotion">
    <fieldset class="motionDeadline">
        <legend><?= $t('motdead_title') ?></legend>
        <div class="description">&nbsp;</div>
        <div class="options">
            <label class="radio-label">
                <span class="title"><?= $t('motdead_no') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio(
                        'SiteCreateForm2[motionsDeadlineExists]',
                        $model->motionDeadline === null,
                        ['value' => 0]
                    ); ?>
                </span>
            </label>
            <label class="radio-label broad">
                <span class="title long"><?= $t('motdead_yes') ?></span>
                <span class="description">
                    <span class="input-group date">
                        <input type="text" class="form-control" name="SiteCreateForm2[motionsDeadline]"
                               value="<?= Html::encode($date) ?>" data-locale="<?= Html::encode($locale) ?>">
                        <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </span>
                <span class="input">
                    <?= Html::radio(
                        'SiteCreateForm2[motionsDeadlineExists]',
                        $model->motionDeadline === null,
                        ['value' => 1]
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
