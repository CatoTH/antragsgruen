<?php

use app\models\forms\SiteCreateForm;
use yii\helpers\Html;

/**
 * @var string[] $errors
 * @var SiteCreateForm $model
 * @var \Callable $t
 */

?>
<div class="step-pane active" id="panelLanguage" data-tab="stepLanguage" data-url="/createsite?language=LNG">
    <fieldset class="language">
        <?php
        if (count($errors) > 0) {
            echo '<div class="alert alert-danger" role="alert">
        <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
        <span class="sr-only">Error:</span>' . nl2br(Html::encode(implode("\n", $errors))) .
                '</div>';
        }
        ?>
        <legend><?= $t('language_title') ?></legend>
        <div class="options">
            <?php
            foreach (\app\components\yii\MessageSource::getBaseLanguages() as $key => $name) {
                ?>
                <label class="radio-label value-motion">
                    <span class="title"><?= Html::encode($name) ?></span>
                    <span class="description"></span>
                    <span class="input">
                    <?= Html::radio('SiteCreateForm[language]', $model->language === $key, ['value' => $key]); ?>
                </span>
                </label>
                <?php
            }
            ?>
        </div>
    </fieldset>
    <div class="navigation">
        <button class="btn btn-lg btn-next btn-primary"><span class="icon-chevron-right"></span> <?= $t('next') ?>
        </button>
    </div>
</div>
