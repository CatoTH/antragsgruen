<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\sectionTypes\TextSimple;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var Motion $draft
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->robotsNoindex = true;
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(\Yii::t('amend', 'merge_bread'));
$layout->loadFuelux();

$title       = str_replace('%TITLE%', $motion->motionType->titleSingular, \Yii::t('amend', 'merge_title'));
$this->title = $title . ': ' . $motion->getTitleWithPrefix();

/** @var MotionSection[] $newSections */
$newSections = [];
foreach ($motion->getSortedSections(false) as $section) {
    $newSections[$section->sectionId] = $section;
}
foreach ($draft->sections as $section) {
    if (!isset($newSections[$section->sectionId])) {
        $newSections[$section->sectionId] = $section;
    }
}

echo '<h1>' . Html::encode($motion->getTitleWithPrefix()) . '</h1>';

echo '<div class="motionData">';

if (!$motion->getMyConsultation()->getSettings()->minimalisticUI) {
    $skip_drafts = true;
    include(__DIR__ . DIRECTORY_SEPARATOR . '_view_motiondata.php');
}

echo '<section class="motionTextHolder">
<h2 class="green">' . \Yii::t('amend', 'merge_new_text') . '</h2>
<div class="content">';

echo '<div class="alert alert-info" role="alert">' .
    \Yii::t('motion', 'merging_draft_warning') .
    '</div>';

foreach ($motion->getSortedSections(false) as $section) {
    $type = $section->getSettings();
    if ($type->type == \app\models\sectionTypes\ISectionType::TYPE_TITLE) {
        $htmlId   = 'sections_' . $type->id;
        echo '<div class="form-group paragraph" id="section_holder_' . $type->id . '">';
        echo '<label for="' . $htmlId . '">' . Html::encode($type->title) . '</label>';
        echo '<div class="text textOrig';
        if ($section->getSettings()->fixedWidth) {
            echo ' fixedWidthFont';
        }
        echo '" id="' . $htmlId . '">' . Html::encode($section->data);
        echo '</div></div>';

    } elseif ($type->type == \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
        /** @var TextSimple $simpleSection */
        $simpleSection = $section->getSectionType();

        $htmlId   = 'sections_' . $type->id;

        echo '<div class="form-group paragraph" id="section_holder_' . $type->id . '">';
        echo '<label for="' . $htmlId . '">' . Html::encode($type->title) . '</label>';

        echo '<div class="text textOrig';
        if ($section->getSettings()->fixedWidth) {
            echo ' fixedWidthFont';
        }
        echo '" id="' . $htmlId . '">';

        if (isset($newSections[$section->sectionId])) {
            echo $newSections[$section->sectionId]->dataRaw;
        } else {
            echo $simpleSection->getMotionTextWithInlineAmendments($changesets);
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

echo '</div></section>';


$editorials = [];
foreach ($motion->getVisibleAmendments(false) as $amendment) {
    if ($amendment->changeEditorial != '') {
        $str          = '<div class="amendment content"><h3>';
        $str          .= str_replace(
            ['%TITLE%', '%INITIATOR%'],
            [$amendment->titlePrefix, $amendment->getInitiatorsStr()],
            \Yii::t('amend', 'merge_amend_by')
        );
        $str          .= '</h3>';
        $str          .= '<div class="text">';
        $str          .= $amendment->changeEditorial;
        $str          .= '</div></div>';
        $editorials[] = $str;
    }
}
if (count($editorials) > 0) {
    echo '<section class="editorialAmendments">
<h2 class="green">' . \Yii::t('amend', 'merge_amend_editorials') . '</h2>
<div>';
    echo implode('', $editorials);
    echo '</div></section>';
}
