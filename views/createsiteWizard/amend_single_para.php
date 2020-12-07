<?php
use yii\helpers\Html;

/**
 * @var \app\models\forms\SiteCreateForm $model
 * @var Callable $t
 */

?>
<div class="step-pane active" id="panelAmendSinglePara" data-tab="stepAmendments">
    <fieldset class="amendSinglePara">
        <legend><?= $t('amend_singlepara_title') ?></legend>
        <div class="description"><?= $t('amend_singlepara_desc') ?></div>
        <div class="options">
            <label class="radio-checkbox-label radio-label two-lines value-1">
                <span class="title"><?= $t('amend_singlepara_single') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[amendSinglePara]', $model->amendSinglePara, ['value' => 1]); ?>
                </span>
            </label>
            <label class="radio-checkbox-label radio-label two-lines value-0">
                <span class="title"><?= $t('amend_singlepara_multi') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[amendSinglePara]', !$model->amendSinglePara, ['value' => 0]); ?>
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
