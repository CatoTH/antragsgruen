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
    $motionData[] = $amendment->getFormattedTitlePrefix() . Yii::t('amend', 'amend_for') . $amendment->getMyMotion()->getFormattedTitlePrefix();
    $motionData[] = $amendment->getMyMotion()->title;
    $text         = '';
    foreach ($amendment->getSortedSections(true) as $section) {
        $text .= $section->getSectionType()->getAmendmentPlainHtml();
    }
    $motionData[] = str_replace("\r", "", $text);
    $motionData[] = str_replace("\r", "", $amendment->changeExplanation);
    $initiators   = $amendment->getInitiators();
    if (count($initiators) > 0) {
        if ($initiators[0]->personType == \app\models\db\ISupporter::PERSON_ORGANIZATION) {
            $motionData[] = $initiators[0]->organization;
        } else {
            $motionData[] = $initiators[0]->name;
        }
    } else {
        $motionData[] = '';
    }
    $topics = [];
    foreach ($amendment->getMyMotion()->getPublicTopicTags() as $tag) {
        $topics[] = $tag->title;
    }
    $motionData[] = implode(', ', $topics);
    $data[]       = $motionData;
}


$fp = fopen('php://output', 'w');

fputcsv($fp, ['Identifier', 'Title', 'Text', 'Reason', 'Submitter', 'Category'], ';', '"', "\\");

foreach ($data as $arr) {
    fputcsv($fp, $arr, ';', '"', "\\");
}
fclose($fp);
