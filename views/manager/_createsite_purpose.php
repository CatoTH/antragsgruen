<?php
use app\models\forms\SiteCreateForm2;
use yii\helpers\Html;

/**
 * @var string[] $errors
 * @var SiteCreateForm2 $model
 * @var \Callable $t
 */

?>
<div class="step-pane active" id="panelPurpose" data-tab="stepPurpose">
    <fieldset class="wording">
        <?php
        if (count($errors) > 0) {
            echo '<div class="alert alert-danger" role="alert">
        <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
        <span class="sr-only">Error:</span>' . nl2br(Html::encode(implode("\n", $errors))) .
                '</div>';
        }
        ?>
        <legend>Was soll diskutiert werden?</legend>
        <div class="description">Das wirkt sich ausschließlich auf das &quot;Wording&quot; aus.</div>
        <div class="options">
            <label class="radio-label">
                <div class="title">Anträge</div>
                <div class="description"></div>
                <div class="input">
                    <?= Html::radio(
                        'SiteCreateForm2[wording]',
                        $model->wording == SiteCreateForm2::WORDING_MOTIONS,
                        ['value' => SiteCreateForm2::WORDING_MOTIONS, "data-wording-name" => "motion"]
                    ); ?>
                </div>
            </label>
            <label class="radio-label">
                <div class="title">Programm</div>
                <div class="description">(Wahl-/Partei)&shy;Programme</div>
                <div class="input">
                    <?= Html::radio(
                        'SiteCreateForm2[wording]',
                        $model->wording == SiteCreateForm2::WORDING_MANIFESTO,
                        ['value' => SiteCreateForm2::WORDING_MANIFESTO, "data-wording-name" => "manifesto"]
                    ); ?>
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
