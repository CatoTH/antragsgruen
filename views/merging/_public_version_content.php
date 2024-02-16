<?php

use app\models\db\Motion;
use app\models\mergeAmendments\Draft;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var Draft $draft
 */

$changesets = [];
foreach ($motion->getSortedSections(false) as $section) {
    $type = $section->getSettings();
    if ($type->type === ISectionType::TYPE_TITLE) {
        $content = ($draft->sections[$section->sectionId] ?? '');
        $htmlId  = 'sections_' . $type->id;
        echo '<div class="form-group paragraph" id="section_holder_' . $type->id . '">';
        echo '<label for="' . $htmlId . '">' . Html::encode($type->title) . '</label>';
        echo '<div class="text motionTextFormattings textOrig ICE-Tracking';
        if ($section->getSettings()->fixedWidth) {
            echo ' fixedWidthFont';
        }
        echo '" id="' . $htmlId . '">' . Html::encode($content);
        echo '</div></div>';
    } elseif ($type->type === ISectionType::TYPE_TEXT_SIMPLE) {
        /** @var TextSimple $simpleSection */
        $simpleSection = $section->getSectionType();

        $htmlId = 'sections_' . $type->id;

        echo '<div class="form-group paragraph" id="section_holder_' . $type->id . '">';
        echo '<label for="' . $htmlId . '">' . Html::encode($type->title) . '</label>';

        echo '<div class="text motionTextFormattings textOrig ICE-Tracking';
        if ($section->getSettings()->fixedWidth) {
            echo ' fixedWidthFont';
        }
        echo '" id="' . $htmlId . '">';

        $paragraphs = [];
        foreach ($section->getTextParagraphLines() as $para) {
            $paragraphs[] = $draft->paragraphs[$section->sectionId . '_' . $para->paragraphWithLineSplit]->text;
        }
        echo implode("\n", $paragraphs);

        echo '</div></div>';
    } else {
        // @TODO Support drafts
        echo $section->getSectionType()->getSimple(false);
    }
}
