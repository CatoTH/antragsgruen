<?php
use yii\helpers\Html;

/**
 * @var \app\models\forms\SiteCreateForm $model
 * @var Callable $t
 */

?>
<div class="step-pane active" id="panelApplicationType" data-tab="stepSpecial">
    <fieldset class="applicationType">
        <legend><?= $t('applicationtype_title') ?></legend>
        <div class="description"><?= $t('applicationtype_desc') ?></div>
        <div class="options">
            <label class="radio-checkbox-label radio-label two-lines value-1">
                <span class="title long"><?= $t('applicationtype_text') ?></span>
                <span class="description"><?= $t('applicationtype_text_desc') ?></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[applicationType]', $model->applicationType == 1, ['value' => 1]); ?>
                </span>
            </label>
            <label class="radio-checkbox-label radio-label two-lines value-2">
                <span class="title long"><?= $t('applicationtype_pdf') ?></span>
                <span class="description"><?= $t('applicationtype_pdf_desc') ?></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[applicationType]', $model->applicationType == 2, ['value' => 2]); ?>
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
