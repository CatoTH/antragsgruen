<?php

/**
 * @var $this yii\web\View
 * @var ConsultationMotionType $motionType
 * @var string $category
 */

use app\models\db\ConsultationMotionType;
use app\components\{HTMLTools, yii\MessageSource, UrlHelper};
use yii\helpers\Html;

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout = $controller->layoutParams;

$layout->addCSS('css/backend.css');

$this->title = Yii::t('admin', 'translating_motion_type') . ': ' . $motionType->titlePlural;
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_language'));
$layout->bodyCssClasses[] = 'adminTranslationForm';

$existingTranslations = [];
foreach ($motionType->consultationTexts as $consultationText) {
    if (!isset($existingTranslations[$consultationText->category])) {
        $existingTranslations[$consultationText->category] = [];
    }
    $existingTranslations[$consultationText->category][$consultationText->textId] = $consultationText;
}

echo Html::beginForm('', 'post', ['id' => 'translationForm', 'class' => 'adminForm']);

?>

    <h1><?= Html::encode($this->title) ?></h1>
    <div class="content">
        <div class="alert alert-info"><?= Yii::t('admin', 'translating_motion_hint') ?></div>
        <?php
        echo $controller->showErrors();
        ?>
    </div>

<?php


foreach (MessageSource::getMotionTypeChangableTexts() as $categoryId => $textIds) {
    echo '<h2 class="green">' . Html::encode(MessageSource::getTranslatableCategories()[$categoryId]) . '</h2><div class="content">';

    foreach ($textIds as $textId) {
        /** @var \app\models\db\ConsultationText|null $existingText */
        $existingText = $existingTranslations[$categoryId][$textId] ?? null;
        $value = ($existingText ? $existingText->text : '');
        $htmlId = 'string_' . $categoryId . '_' . $textId;
        ?>
        <div class="stdTwoCols">
            <label class="halfColumn" for="<?= $htmlId ?>">
                <span class="description"><?= nl2br(Html::encode(Yii::t($categoryId, $textId))) ?></span>
                <span class="identifier"><?= Html::encode($textId) ?></span>
            </label>
            <div class="halfColumn">
                <?= HTMLTools::smallTextarea('categories[' . $categoryId . '][' . $textId . ']', ['class' => 'form-control', 'id' => $htmlId], $value) ?>
            </div>
        </div>
        <?php
    }
    echo '</div>';
}

echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">' . Yii::t('base', 'save') . '</button>
</div>';

echo Html::endForm();
