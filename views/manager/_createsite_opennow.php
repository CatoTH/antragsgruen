<?php
use app\models\forms\SiteCreateForm2;
use yii\helpers\Html;

/**
 * @var SiteCreateForm2 $model
 * @var \Callable $t
 */

?>
<div class="step-pane active" id="panelOpenNow" data-tab="stepSpecial">
    <fieldset class="motionOpenNow">
        <legend><?= $t('opennow_title') ?></legend>
        <div class="description"><?= $t('opennow_desc') ?></div>
        <div class="options">
            <label class="radio-label two-lines">
                <span class="title long"><?= $t('opennow_no') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm2[openNow]', !$model->openNow, ['value' => 0]); ?>
                </span>
            </label>
            <label class="radio-label">
                <span class="title"><?= $t('opennow_yes') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm2[openNow]', $model->openNow, ['value' => 1]); ?>
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
