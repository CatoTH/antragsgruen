<?php
use yii\helpers\Html;

/**
 * @var \app\models\forms\SiteCreateForm $model
 * @var Callable $t
 */

?>
<div class="step-pane active" id="panelSpeechLogin" data-tab="stepSpecial">
    <fieldset class="speechLogin">
        <legend><?= $t('speech_login_title') ?></legend>
        <div class="description"><?= $t('speech_login_desc') ?></div>
        <div class="options">
            <label class="radio-checkbox-label radio-label value-0">
                <span class="title"><?= $t('speech_login_no') ?></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[speechLogin]', !$model->speechLogin, ['value' => 0]); ?>
                </span>
            </label>
            <label class="radio-checkbox-label radio-label value-1">
                <span class="title"><?= $t('speech_login_yes') ?></span>
                <span class="input">
                    <?= Html::radio('SiteCreateForm[speechLogin]', $model->speechLogin, ['value' => 1]); ?>
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
