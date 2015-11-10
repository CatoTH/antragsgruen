<?php

use app\models\db\Amendment;

/**
 * @var \yii\web\View $this
 * @var Amendment[] $amendments
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;


$data = [];
foreach ($amendments as $amendment) {
    $motionData   = [];
    $motionData[] = $amendment->titlePrefix . \Yii::t('amend', 'amend_for') . $amendment->motion->titlePrefix;
    $motionData[] = $amendment->motion->title;
    $text         = '';
    foreach ($amendment->getSortedSections(true) as $section) {
        $text .= $section->getSectionType()->getAmendmentPlainHtml();
    }
    $motionData[] = $text;
    $motionData[] = $amendment->changeExplanation;
    $motionData[] = $amendment->getInitiatorsStr();
    $topics       = [];
    foreach ($amendment->motion->tags as $tag) {
        $topics[] = $tag->title;
    }
    $motionData[] = implode(', ', $topics);
    $data[]       = $motionData;
}


$fp = fopen('php://output', 'w');

fputcsv($fp, ['Identifier', 'Title', 'Text', 'Reason', 'Submitter', 'Category'], ';', '"');

foreach ($data as $arr) {
    fputcsv($fp, $arr, ';', '"');
}
fclose($fp);
