<?php
use app\models\forms\SiteCreateForm2;
use yii\helpers\Html;

/**
 * @var SiteCreateForm2 $model
 * @var \Callable $t
 */

?>
<div class="step-pane active" id="panelUseScreening" data-tab="stepMotion">
    <fieldset class="useScreening">
        <legend>
            <span class="only-motion"><?=$t('screening_mot_title')?></span>
            <span class="only-manifesto"><?=$t('screening_man_title')?></span>
        </legend>
        <div class="description"><?=$t('screening_desc')?></div>
        <div class="options">
            <label class="radio-label two-lines">
                <span class="title"><?=$t('screening_yes')?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm2[useScreening]', $model->useScreening, ['value' => 1]); ?>
                </span>
            </label>
            <label class="radio-label two-lines">
                <span class="title"><?=$t('screening_no')?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm2[useScreening]', !$model->useScreening, ['value' => 0]); ?>
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
