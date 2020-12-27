<?php
use yii\helpers\Html;

/**
 * @var \app\models\forms\SiteCreateForm $model
 * @var Callable $t
 */

?>
<div class="step-pane active" id="panelSingleMotion" data-tab="stepMotions">
    <fieldset class="singleMotion">
        <legend>
            <span class="only-motion"><?=$t('single_mot_title')?></span>
            <span class="only-manifesto"><?=$t('single_man_title')?></span>
        </legend>
        <div class="description">
            <span class="only-motion"><?=$t('single_mot_desc')?></span>
            <span class="only-manifesto"><?=$t('single_man_desc')?></span>
        </div>
        <div class="options">
            <label class="radio-checkbox-label radio-label value-1">
                <span class="title"><?=$t('single_one')?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[singleMotion]', $model->singleMotion, ['value' => 1]); ?>
                </span>
            </label>
            <label class="radio-checkbox-label radio-label value-0">
                <span class="title"><?=$t('single_multi')?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[singleMotion]', !$model->singleMotion, ['value' => 0]); ?>
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
