<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\forms\MotionMergeAmendmentsForm;
use app\models\sectionTypes\TextSimple;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var MotionMergeAmendmentsForm $form
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->addBreadcrumb($motion->motionType->titleSingular, UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb('Überarbeiten');
$layout->loadCKEditor();
$layout->loadFuelux();
$layout->addOnLoadJS('$.Antragsgruen.motionMergeAmendmentsForm();');

$title       = str_replace('%NAME%', $motion->motionType->titleSingular, '%NAME% überarbeiten');
$this->title = $title . ': ' . $motion->getTitleWithPrefix();

echo '<h1>' . Html::encode($this->title) . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'motionMergeForm fuelux']);


echo '<section class="newMotion">
<h2 class="green">' . 'Neuer Antragstext' . '</h2>
<div class="content">';

$changesets = [];

foreach ($motion->getSortedSections(false) as $section) {
    $type = $section->consultationSetting;
    if ($section->consultationSetting->type == \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
        /** @var TextSimple $simpleSection */
        $simpleSection = $section->getSectionType();

        $nameBase = 'sections[' . $type->id . ']';
        $htmlId   = 'sections_' . $type->id;

        echo '<div class="form-group wysiwyg-textarea" id="section_holder_' . $type->id . '" data-fullHtml="0">';
        echo '<label for="' . $htmlId . '">' . Html::encode($type->title) . '</label>';

        echo '<textarea name="' . $nameBase . '[raw]" class="raw" id="' . $htmlId . '" ' .
            'title="' . Html::encode($type->title) . '"></textarea>';
        echo '<textarea name="' . $nameBase . '[consolidated]" class="consolidated" ' .
            'title="' . Html::encode($type->title) . '"></textarea>';
        echo '<div class="texteditor" data-track-changed="1" id="' . $htmlId . '_wysiwyg" ' .
            'title="' . Html::encode($type->title) . '">';

        echo $simpleSection->getMotionTextWithInlineAmendments($changesets);

        echo '</div>';
        echo '</div>';
    } else {
        echo $section->getSectionType()->getMotionFormField();
    }
}

echo '</div></section>';

$jsStati = [
    'accepted'          => Amendment::STATUS_ACCEPTED,
    'rejected'          => Amendment::STATUS_REJECTED,
    'modified_accepted' => Amendment::STATUS_MODIFIED_ACCEPTED,
];

echo '<section class="newAmendments" data-stati="' . Html::encode(json_encode($jsStati)) . '">';
\app\views\motion\LayoutHelper::printAmendmentStatusSetter($motion->getVisibleAmendments());
echo '</section>';


echo '<div class="submitHolder content"><button type="submit" name="save" class="btn btn-primary">
    <span class="glyphicon glyphicon-chevron-right"></span> Weiter
</button></div>';

echo Html::endForm();
