<?php

use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\sectionTypes\TextSimple;
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var Motion $draft
 */

/** @var MotionSection[] $newSections */
$newSections = [];
foreach ($motion->getSortedSections(false) as $section) {
    $newSections[$section->sectionId] = $section;
}
foreach ($draft->sections as $section) {
    $newSections[$section->sectionId] = $section;
}

$changesets = [];
foreach ($motion->getSortedSections(false) as $section) {
    $type = $section->getSettings();
    if ($type->type == \app\models\sectionTypes\ISectionType::TYPE_TITLE) {
        $htmlId = 'sections_' . $type->id;
        echo '<div class="form-group paragraph" id="section_holder_' . $type->id . '">';
        echo '<label for="' . $htmlId . '">' . Html::encode($type->title) . '</label>';
        echo '<div class="text textOrig ICE-Tracking';
        if ($section->getSettings()->fixedWidth) {
            echo ' fixedWidthFont';
        }
        echo '" id="' . $htmlId . '">' . Html::encode($section->data);
        echo '</div></div>';

    } elseif ($type->type == \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
        /** @var TextSimple $simpleSection */
        $simpleSection = $section->getSectionType();

        $htmlId = 'sections_' . $type->id;

        echo '<div class="form-group paragraph" id="section_holder_' . $type->id . '">';
        echo '<label for="' . $htmlId . '">' . Html::encode($type->title) . '</label>';

        echo '<div class="text textOrig ICE-Tracking';
        if ($section->getSettings()->fixedWidth) {
            echo ' fixedWidthFont';
        }
        echo '" id="' . $htmlId . '">';

        if (isset($newSections[$section->sectionId])) {
            echo $newSections[$section->sectionId]->dataRaw;
        } else {
            $amendmentIds = [];
            foreach ($motion->getVisibleAmendments() as $amendment) {
                $amendmentIds[] = $amendment->id;
            }
            echo $simpleSection->getMotionTextWithInlineAmendments($amendmentIds, $changesets);
        }

        echo '</div></div>';
    } else {
        if (isset($newSections[$section->sectionId])) {
            echo $newSections[$section->sectionId]->getSectionType()->getSimple(false);
        } else {
            echo $section->getSectionType()->getSimple(false);
        }
    }
}
