<?php

/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var ConsultationMotionType $motionType
 * @var WordingOverridesForm $form
 */

use app\models\db\ConsultationMotionType;
use app\models\forms\WordingOverridesForm;
use app\components\{Html, HTMLTools, yii\MessageSource, UrlHelper};


$layout->addCSS('css/backend.css');

$this->title = Yii::t('admin', 'translating_motion_type') . ': ' . $motionType->titlePlural;
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_language'));
$layout->bodyCssClasses[] = 'adminTranslationForm';

echo Html::beginForm('', 'post', ['id' => 'translationForm', 'class' => 'adminForm']);

?>

    <h1><?= Html::encode($this->title) ?></h1>
    <div class="content">
        <div class="alert alert-info"><?= Yii::t('admin', 'translating_motion_hint') ?></div>
        <?php
        echo \app\components\FlashMessages::render();
        ?>
    </div>

<?php


foreach (MessageSource::getMotionTypeChangableTexts() as $categoryId => $textIds) {
    echo '<h2 class="green">' . Html::encode(MessageSource::getTranslatableCategories()[$categoryId]) . '</h2><div class="content">';

    $existingTranslations = $form->getDisplayValues($categoryId);
    foreach ($textIds as $textId) {
        $value = $existingTranslations[$textId] ?? '';
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
