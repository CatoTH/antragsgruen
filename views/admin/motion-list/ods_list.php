<?php

use app\components\HTMLTools;
use app\models\db\ConsultationMotionType;
use app\models\db\Motion;
use CatoTH\HTML2OpenDocument\Spreadsheet;

/**
 * @var $this yii\web\View
 * @var Motion[] $motions
 * @var bool $textCombined
 * @var ConsultationMotionType $motionType
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;

$DEBUG = false;

/** @var \app\models\settings\AntragsgruenApp $params */
$params = \yii::$app->params;

$doc = new Spreadsheet([
    'tmpPath'   => $params->tmpDir,
    'trustHtml' => true,
]);

$currCol = $firstCol = 1;


$hasTags        = ($consultation->tags > 0);
$hasAgendaItems = false;
foreach ($motions as $motion) {
    if ($motion->agendaItem) {
        $hasAgendaItems = true;
    }
}

if ($hasAgendaItems) {
    $COL_AGENDA_ITEM = $currCol++;
}
$COL_PREFIX    = $currCol++;
$COL_INITIATOR = $currCol++;
$COL_TEXTS     = [];
if ($textCombined) {
    $COL_TEXTS[] = $currCol++;
} else {
    foreach ($motionType->motionSections as $section) {
        $COL_TEXTS[$section->id] = $currCol++;
    }
}
if ($hasTags) {
    $COL_TAGS = $currCol++;
}
$COL_CONTACT   = $currCol++;
$COL_PROCEDURE = $currCol++;


// Title

$doc->setCell(1, $firstCol, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'all_motions_title'));
$doc->setCellStyle(1, $firstCol, [], [
    'fo:font-size'   => '16pt',
    'fo:font-weight' => 'bold',
]);
$doc->setMinRowHeight(1, 1.5);


// Heading

if ($hasAgendaItems) {
    $doc->setCell(2, $COL_AGENDA_ITEM, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'agenda_item'));
    $doc->setCellStyle(2, $COL_AGENDA_ITEM, [], ['fo:font-weight' => 'bold']);
}

$doc->setCell(2, $COL_PREFIX, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'prefix_short'));
$doc->setCellStyle(2, $COL_PREFIX, [], ['fo:font-weight' => 'bold']);

$doc->setCell(2, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'initiator'));
$doc->setColumnWidth($COL_INITIATOR, 6);

if ($textCombined) {
    $doc->setCell(2, $COL_TEXTS[0], Spreadsheet::TYPE_TEXT, \Yii::t('export', 'text'));
    $doc->setColumnWidth($COL_TEXTS[0], 10);
} else {
    foreach ($motionType->motionSections as $section) {
        $doc->setCell(2, $COL_TEXTS[$section->id], Spreadsheet::TYPE_TEXT, $section->title);
        $doc->setColumnWidth($COL_TEXTS[$section->id], 10);
    }
}
if (isset($COL_TAGS)) {
    $doc->setCell(2, $COL_TAGS, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'tags'));
    $doc->setColumnWidth($COL_TAGS, 6);
}
$doc->setCell(2, $COL_CONTACT, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'contact'));
$doc->setColumnWidth($COL_CONTACT, 6);
$doc->setCell(2, $COL_PROCEDURE, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'procedure'));
$doc->setColumnWidth($COL_PROCEDURE, 6);

$doc->drawBorder(1, $firstCol, 2, $COL_PROCEDURE, 1.5);


// Motions

$row = 2;

foreach ($motions as $motion) {
    $row++;

    $initiatorNames    = [];
    $initiatorContacts = [];
    foreach ($motion->getInitiators() as $supp) {
        $initiatorNames[] = $supp->getNameWithResolutionDate(false);
        if ($supp->contactEmail != '') {
            $initiatorContacts[] = $supp->contactEmail;
        }
        if ($supp->contactPhone != '') {
            $initiatorContacts[] = $supp->contactPhone;
        }
    }

    if ($hasAgendaItems && $motion->agendaItem) {
        $doc->setCell($row, $COL_AGENDA_ITEM, Spreadsheet::TYPE_TEXT, $motion->agendaItem->getShownCode(true));
    }
    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $motion->titlePrefix);
    $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, implode(', ', $initiatorNames));
    $doc->setCell($row, $COL_CONTACT, Spreadsheet::TYPE_TEXT, implode("\n", $initiatorContacts));

    if ($textCombined) {
        $text = '';
        foreach ($motion->getSortedSections(true) as $section) {
            $text .= $section->getSettings()->title . "\n\n";
            $text .= $section->getSectionType()->getMotionODS();
            $text .= "\n\n";
        }
        $text = HTMLTools::correctHtmlErrors($text);
        $doc->setCell($row, $COL_TEXTS[0], Spreadsheet::TYPE_HTML, $text);
    } else {
        foreach ($motionType->motionSections as $section) {
            $text = '';
            foreach ($motion->getActiveSections() as $sect) {
                if ($sect->sectionId == $section->id) {
                    $text = $sect->getSectionType()->getMotionODS();
                }
            }
            $text = HTMLTools::correctHtmlErrors($text);
            $doc->setCell($row, $COL_TEXTS[$section->id], Spreadsheet::TYPE_HTML, $text);
        }
    }
    if (isset($COL_TAGS)) {
        $tags = [];
        foreach ($motion->tags as $tag) {
            $tags[] = $tag->title;
        }
        $doc->setCell($row, $COL_TAGS, Spreadsheet::TYPE_TEXT, implode("\n", $tags));
    }
}

echo $doc->finishAndGetDocument();
