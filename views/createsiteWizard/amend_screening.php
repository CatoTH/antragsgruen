<?php
use yii\helpers\Html;

/**
 * @var \app\models\forms\SiteCreateForm $model
 * @var Callable $t
 */

?>
<div class="step-pane active" id="panelAmendScreening" data-tab="stepAmendments">
    <fieldset class="amendScreening">
        <legend><?= $t('screening_amend_title') ?></legend>
        <div class="description"><?= $t('screening_desc') ?></div>
        <div class="options">
            <label class="radio-checkbox-label radio-label two-lines value-0">
                <span class="title"><?= $t('screening_no') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[amendScreening]', !$model->amendScreening, ['value' => 0]); ?>
                </span>
            </label>
            <label class="radio-checkbox-label radio-label two-lines value-1">
                <span class="title"><?= $t('screening_yes') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[amendScreening]', $model->amendScreening, ['value' => 1]); ?>
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
