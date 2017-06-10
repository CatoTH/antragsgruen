<?php
use yii\helpers\Html;

/**
 * @var app\models\forms\SiteCreateForm $model
 * @var string $mode
 * @var \Callable $t
 */

?>
<div class="step-pane active" id="panelAgenda" data-tab="stepSpecial">
    <fieldset class="hasAgenda">
        <legend><?= $t('agenda_title') ?></legend>
        <div class="description"><?= $t('agenda_desc') ?></div>
        <div class="options">
            <label class="radio-label value-0">
                <span class="title"><?= $t('agenda_no') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[hasAgenda]', !$model->hasAgenda, ['value' => 0]); ?>
                </span>
            </label>
            <label class="radio-label value-1">
                <span class="title"><?= $t('agenda_yes') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[hasAgenda]', $model->hasAgenda, ['value' => 1]); ?>
                </span>
            </label>
        </div>
    </fieldset>
    <div class="navigation">
        <button class="btn btn-lg btn-prev"><span class="icon-chevron-left"></span> <?= $t('prev') ?></button>
        <?php if ($mode == 'sandbox') { ?>
            <button type="submit" class="btn btn-lg btn-next btn-primary" name="create">
                <span class="icon-chevron-right"></span> <?= $t('finish') ?>
            </button>
        <?php } else { ?>
        <button class="btn btn-lg btn-next btn-primary"><span class="icon-chevron-right"></span> <?= $t('next') ?>
        </button>
        <?php } ?>
    </div>
</div>
