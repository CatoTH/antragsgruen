<?php

use app\models\db\Motion;

/**
 * @var \yii\web\View $this
 * @var Motion[] $motions
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;


$data = [];
foreach ($motions as $motion) {
    $motionData   = [];
    $motionData[] = $motion->getFormattedTitlePrefix(\app\models\layoutHooks\Layout::CONTEXT_MOTION_LIST);
    $motionData[] = $motion->title;
    $text         = '';
    $reason       = '';
    foreach ($motion->getSortedSections(true) as $section) {
        $html = $section->getSectionType()->getMotionPlainHtml();
        if ($section->getSettings()->title == Yii::t('export', 'motion_reason')) {
            $reason .= $html;
        } else {
            $text .= $html;
        }
    }
    $motionData[] = $text;
    $motionData[] = $reason;
    $initiators = $motion->getInitiators();
    if (count($initiators) > 0) {
        if ($initiators[0]->personType == \app\models\db\ISupporter::PERSON_ORGANIZATION) {
            $motionData[] = $initiators[0]->organization;
        } else {
            $motionData[] = $initiators[0]->name;
        }
    } else {
        $motionData[] = '';
    }
    $topics       = [];
    foreach ($motion->getPublicTopicTags() as $tag) {
        $topics[] = $tag->title;
    }
    $motionData[] = implode(', ', $topics);
    $data[]       = $motionData;
}


$fp = fopen('php://output', 'w');

fputcsv($fp, ['Identifier', 'Title', 'Text', 'Reason', 'Submitter', 'Category'], ';', '"');

foreach ($data as $arr) {
    fputcsv($fp, $arr, ';', '"', "\\");
}
fclose($fp);
