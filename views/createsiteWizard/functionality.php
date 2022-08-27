<?php
use app\models\forms\SiteCreateForm;
use yii\helpers\Html;

/**
 * @var string[] $errors
 * @var SiteCreateForm $model
 * @var Callable $t
 */

?>
<div class="step-pane active" id="panelFunctionality" data-tab="stepPurpose">
    <fieldset class="functionality">
        <?php
        if (count($errors) > 0) {
            echo '<div class="alert alert-danger" role="alert">
        <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
        <span class="sr-only">Error:</span>' . nl2br(Html::encode(implode("\n", $errors))) .
                '</div>';
        }
        ?>
        <legend><?=$t('functionality_title')?></legend>
        <div class="description"><?=$t('functionality_desc')?></div>
        <div class="options">
            <label class="radio-checkbox-label checkbox-label value-motion">
                <span class="title"><?=$t('functionality_motions')?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::checkbox(
                        'SiteCreateForm[functionality][]',
                        in_array(SiteCreateForm::FUNCTIONALITY_MOTIONS, $model->functionality),
                        ['value' => SiteCreateForm::FUNCTIONALITY_MOTIONS, "data-wording-name" => "motion"]
                    ); ?>
                </span>
            </label>
            <label class="radio-checkbox-label checkbox-label description-first value-manifesto">
                <span class="description"><?=$t('functionality_manifesto_desc')?></span>
                <span class="title"><?=$t('functionality_manifesto')?></span>
                <span class="input">
                    <?= Html::checkbox(
                        'SiteCreateForm[functionality][]',
                        in_array(SiteCreateForm::FUNCTIONALITY_MANIFESTO, $model->functionality),
                        ['value' => SiteCreateForm::FUNCTIONALITY_MANIFESTO, "data-wording-name" => "manifesto"]
                    ); ?>
                </span>
            </label>
            <label class="radio-checkbox-label checkbox-label description-first value-applications">
                <span class="description">&nbsp;</span>
                <span class="title"><?=$t('functionality_applications')?></span>
                <span class="input">
                    <?= Html::checkbox(
                        'SiteCreateForm[functionality][]',
                        in_array(SiteCreateForm::FUNCTIONALITY_APPLICATIONS, $model->functionality),
                        ['value' => SiteCreateForm::FUNCTIONALITY_APPLICATIONS]
                    ); ?>
                </span>
            </label>
            <label class="radio-checkbox-label checkbox-label description-first value-agenda">
                <span class="description">&nbsp;</span>
                <span class="title"><?=$t('functionality_agenda')?></span>
                <span class="input">
                    <?= Html::checkbox(
                        'SiteCreateForm[functionality][]',
                        in_array(SiteCreateForm::FUNCTIONALITY_AGENDA, $model->functionality),
                        ['value' => SiteCreateForm::FUNCTIONALITY_AGENDA]
                    ); ?>
                </span>
            </label>
            <label class="radio-checkbox-label checkbox-label two-lines value-statute">
                <span class="description">&nbsp;</span>
                <span class="title"><?=$t('functionality_statute_amendments')?></span>
                <span class="input">
                    <?= Html::checkbox(
                        'SiteCreateForm[functionality][]',
                        in_array(SiteCreateForm::FUNCTIONALITY_STATUTE_AMENDMENTS, $model->functionality),
                        ['value' => SiteCreateForm::FUNCTIONALITY_STATUTE_AMENDMENTS]
                    ); ?>
                </span>
            </label>
            <label class="radio-checkbox-label checkbox-label description-first value-speech">
                <span class="description">&nbsp;</span>
                <span class="title"><?=$t('functionality_speech')?></span>
                <span class="input">
                    <?= Html::checkbox(
                        'SiteCreateForm[functionality][]',
                        in_array(SiteCreateForm::FUNCTIONALITY_SPEECH_LISTS, $model->functionality),
                        ['value' => SiteCreateForm::FUNCTIONALITY_SPEECH_LISTS]
                    ); ?>
                </span>
            </label>
            <label class="radio-checkbox-label checkbox-label description-first value-votings">
                <span class="description">&nbsp;</span>
                <span class="title"><?=$t('functionality_votings')?></span>
                <span class="input">
                    <?= Html::checkbox(
                        'SiteCreateForm[functionality][]',
                        in_array(SiteCreateForm::FUNCTIONALITY_VOTINGS, $model->functionality),
                        ['value' => SiteCreateForm::FUNCTIONALITY_VOTINGS]
                    ); ?>
                </span>
            </label>
            <label class="radio-checkbox-label checkbox-label description-first value-documents">
                <span class="description"><?=$t('functionality_documents_desc')?></span>
                <span class="title"><?=$t('functionality_documents')?></span>
                <span class="input">
                    <?= Html::checkbox(
                        'SiteCreateForm[functionality][]',
                        in_array(SiteCreateForm::FUNCTIONALITY_DOCUMENTS, $model->functionality),
                        ['value' => SiteCreateForm::FUNCTIONALITY_DOCUMENTS]
                    ); ?>
                </span>
            </label>
        </div>
    </fieldset>
    <div class="navigation">
        <button class="btn btn-lg btn-next btn-primary">
            <span class="icon-chevron-right" aria-hidden="true"></span>
            <?= $t('next') ?>
        </button>
    </div>
</div>
