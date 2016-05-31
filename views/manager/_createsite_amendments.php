<?php
use app\models\forms\SiteCreateForm2;
use yii\helpers\Html;

/**
 * @var SiteCreateForm2 $model
 * @var \Callable $t
 */

?>
<div class="step-pane active" id="panelHasAmendments" data-tab="stepAmendments">
    <fieldset class="hasAmendments">
        <legend><?= $t('amend_title') ?></legend>
        <div class="description">&nbsp;</div>
        <div class="options">
            <label class="radio-label">
                <span class="title"><?= $t('amend_no') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm2[hasAmendments]', !$model->hasAmendments, ['value' => 0]); ?>
                </span>
            </label>
            <label class="radio-label">
                <span class="title"><?= $t('amend_yes') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm2[hasAmendments]', $model->hasAmendments, ['value' => 1]); ?>
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
