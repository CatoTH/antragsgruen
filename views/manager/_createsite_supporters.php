<?php
use app\models\forms\SiteCreateForm2;
use yii\helpers\Html;

/**
 * @var SiteCreateForm2 $model
 * @var \Callable $t
 */

?>
<div class="step-pane active" id="panelNeedsSupporters" data-tab="stepMotion">
    <fieldset class="needsSupporters">
        <legend><?=$t('supporters_title')?></legend>
        <div class="description"><?=$t('supporters_desc')?></div>
        <div class="options">
            <label class="radio-label">
                <span class="title"><?=$t('supporters_no')?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm2[needsSupporters]', !$model->needsSupporters, ['value' => 0]); ?>
                </span>
            </label>
            <label class="radio-label">
                <span class="title"><?=$t('supporters_yes')?></span>
                <span class="description">
                    <input type="number" name="SiteCreateForm2[minSupporters]" value="<?=$model->minSupporters?>"
                        class="minSupporters form-control">
                </span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm2[needsSupporters]', $model->needsSupporters, ['value' => 1]); ?>
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
