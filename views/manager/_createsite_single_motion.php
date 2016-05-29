<?php
use app\models\forms\SiteCreateForm2;
use yii\helpers\Html;

/**
 * @var SiteCreateForm2 $model
 * @var \Callable $t
 */

?>
<div class="step-pane active" id="panelSingleMotion" data-tab="stepMotion">
    <fieldset class="singleMotion">
        <legend>
            <span class="only-motion">Werden mehrere Anträge diskutiert?</span>
            <span class="only-manifesto">Gibt es mehrere Kapitel?</span>
        </legend>
        <div class="description">
            <span class="only-motion">Bei nur einem entfällt die Antragsübersicht</span>
            <span class="only-manifesto">Bei nur einem entfällt die Übersichtsseite</span>
        </div>
        <div class="options">
            <label class="radio-label">
                <div class="title">Nur eins</div>
                <div class="description"></div>
                <div class="input">
                    <?= Html::radio('SiteCreateForm2[singleMotion]', $model->singleMotion, ['value' => 1]); ?>
                </div>
            </label>
            <label class="radio-label">
                <div class="title">Mehrere</div>
                <div class="description"></div>
                <div class="input">
                    <?= Html::radio('SiteCreateForm2[singleMotion]', !$model->singleMotion, ['value' => 0]); ?>
                </div>
            </label>
        </div>
    </fieldset>
    <div class="navigation">
        <button class="btn btn-lg btn-prev"><span class="icon-chevron-left"></span> <?= $t('prev') ?></button>
        <button class="btn btn-lg btn-next btn-primary"><span class="icon-chevron-right"></span> <?= $t('next') ?>
        </button>
    </div>
</div>
