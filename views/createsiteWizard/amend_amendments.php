<?php
use yii\helpers\Html;

/**
 * @var \app\models\forms\SiteCreateForm $model
 * @var \Callable $t
 */

?>
<div class="step-pane active" id="panelAmendAmendments" data-tab="stepAmendments">
    <fieldset class="amendAmendments">
        <legend><?= $t('amend_amendments_title') ?></legend>
        <div class="description"><?= $t('amend_amendments_desc') ?></div>
        <div class="options">
            <label class="radio-label two-lines value-1">
                <span class="title"><?= $t('amend_amendments_no') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[amendAmendments]', $model->amendAmendments, ['value' => 0]); ?>
                </span>
            </label>
            <label class="radio-label two-lines value-0">
                <span class="title"><?= $t('amend_amendments_yes') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[amendAmendments]', !$model->amendAmendments, ['value' => 1]); ?>
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
