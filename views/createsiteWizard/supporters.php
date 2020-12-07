<?php
use yii\helpers\Html;

/**
 * @var \app\models\forms\SiteCreateForm $model
 * @var Callable $t
 */

?>
<div class="step-pane active" id="panelNeedsSupporters" data-tab="stepMotions">
    <fieldset class="needsSupporters">
        <legend><?= $t('supporters_title') ?></legend>
        <div class="description"><?= $t('supporters_desc') ?></div>
        <div class="options">
            <label class="radio-checkbox-label radio-label value-0">
                <span class="title"><?= $t('supporters_no') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[needsSupporters]', !$model->needsSupporters, ['value' => 0]); ?>
                </span>
            </label>
            <label class="radio-checkbox-label radio-label date-label value-1">
                <span class="title long"><?= $t('supporters_yes') ?></span>
                <span class="description">
                    <input type="number" name="SiteCreateForm[minSupporters]" value="<?= $model->minSupporters ?>"
                           class="minSupporters form-control">
                </span>
                <span class="input">
                    <?= Html::radio(
                        'SiteCreateForm[needsSupporters]',
                        $model->needsSupporters,
                        ['value' => 1, 'class' => 'needsSupporters']
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
