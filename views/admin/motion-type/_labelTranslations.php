<?php

use app\components\{HTMLTools, LanguageTools};
use app\models\db\ConsultationMotionType;
use yii\helpers\Html;

/**
 * @var ConsultationMotionType $motionType
 */

$primaryLanguage = LanguageTools::getPrimaryLanguage($motionType->getConsultation());
$otherLanguages = array_values(array_filter(
    LanguageTools::getSupportedLanguages($motionType->getConsultation()->site),
    fn (string $language): bool => $language !== $primaryLanguage
));

if (count($otherLanguages) === 0) {
    return;
}

?>
<section aria-labelledby="motionTypeLabelTranslationsTitle">
<h2 class="green" id="motionTypeLabelTranslationsTitle"><?= Yii::t('admin', 'motion_type_label_transl') ?></h2>
<div class="content">
    <p class="help-block"><?= Yii::t('admin', 'motion_type_label_transl_hi') ?></p>
    <?php foreach ($otherLanguages as $language) { ?>
        <h3><?= Html::encode(LanguageTools::getLanguageIcon($language) . ' ' . LanguageTools::getLanguageName($language)) ?></h3>

        <div class="stdTwoCols">
            <label class="leftColumn" for="typeTitleSingular<?= $language ?>">
                <?= Yii::t('admin', 'motion_type_singular') ?>
            </label>
            <div class="rightColumn"><?php
                $options = [
                    'class' => 'form-control',
                    'id' => 'typeTitleSingular' . $language,
                    'placeholder' => $motionType->titleSingular,
                ];
                echo Html::textInput('labelTranslations[' . $language . '][titleSingular]', $motionType->getLabelTranslation($language, 'titleSingular'), $options);
                ?></div>
        </div>

        <div class="stdTwoCols">
            <label class="leftColumn" for="typeTitlePlural<?= $language ?>">
                <?= Yii::t('admin', 'motion_type_plural') ?>
            </label>
            <div class="rightColumn"><?php
                $options = [
                    'class' => 'form-control',
                    'id' => 'typeTitlePlural' . $language,
                    'placeholder' => $motionType->titlePlural,
                ];
                echo Html::textInput('labelTranslations[' . $language . '][titlePlural]', $motionType->getLabelTranslation($language, 'titlePlural'), $options);
                ?></div>
        </div>

        <div class="stdTwoCols">
            <label class="leftColumn" for="typeCreateTitle<?= $language ?>">
                <?= Yii::t('admin', 'motion_type_create_title') ?>
            </label>
            <div class="rightColumn"><?php
                $options = [
                    'class' => 'form-control',
                    'id' => 'typeCreateTitle' . $language,
                    'placeholder' => $motionType->createTitle,
                ];
                echo HTMLTools::smallTextarea('labelTranslations[' . $language . '][createTitle]', $options, $motionType->getLabelTranslation($language, 'createTitle'));
                ?></div>
        </div>
    <?php } ?>
</div>
</section>
