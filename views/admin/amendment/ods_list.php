<?php

use app\models\sectionTypes\TextSimpleCommon;
use app\components\HTMLTools;
use app\models\db\{Amendment, AmendmentSection, Motion};
use CatoTH\HTML2OpenDocument\Spreadsheet;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var array<array{motion: Motion, amendments: Amendment[]}> $amendments
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;

$DEBUG = false;

$params = \app\models\settings\AntragsgruenApp::getInstance();

/** @noinspection PhpUnhandledExceptionInspection */
$doc = new Spreadsheet([
    'tmpPath'   => $params->getTmpDir(),
    'trustHtml' => true,
]);

$currCol = $firstCol = 1;

$hasAgendaItems = false;
$hasResponsibilities = false;
foreach ($amendments as $amendmentGroup) {
    if ($amendmentGroup['motion']->getMyMotionType()->amendmentsOnly) {
        continue;
    }
    if ($amendmentGroup['motion']->agendaItem) {
        $hasAgendaItems = true;
    }
    if ($amendmentGroup['motion']->responsibilityId || $amendmentGroup['motion']->responsibilityComment) {
        $hasResponsibilities = true;
    }
}

if ($hasAgendaItems) {
    $COL_AGENDA_ITEM = $currCol++;
}
$COL_PREFIX     = $currCol++;
$COL_INITIATOR  = $currCol++;
$COL_FIRST_LINE = $currCol++;
$COL_STATUS     = $currCol++;
$COL_CHANGE     = $currCol++;
$COL_REASON     = $currCol++;
$COL_CONTACT    = $currCol++;
$COL_PROCEDURE  = $currCol++;
$LAST_COL      = $COL_PROCEDURE;
if ($hasResponsibilities) {
    $COL_RESPONSIBILITY = $currCol++;
    $LAST_COL           = $COL_RESPONSIBILITY;
}


// Title

$doc->setCell(1, $firstCol, Spreadsheet::TYPE_TEXT, Yii::t('export', 'amendments'));
$doc->setCellStyle(1, $firstCol, [], [
    'fo:font-size'   => '16pt',
    'fo:font-weight' => 'bold',
]);
$doc->setMinRowHeight(1, 1.5);


// Heading

if ($hasAgendaItems) {
    $doc->setCell(2, $COL_AGENDA_ITEM, Spreadsheet::TYPE_TEXT, Yii::t('export', 'agenda_item'));
    $doc->setCellStyle(2, $COL_AGENDA_ITEM, [], ['fo:font-weight' => 'bold']);
}

$doc->setCell(2, $COL_PREFIX, Spreadsheet::TYPE_TEXT, Yii::t('export', 'prefix_short'));
$doc->setCellStyle(2, $COL_PREFIX, [], ['fo:font-weight' => 'bold']);

$doc->setCell(2, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, Yii::t('export', 'initiator'));
$doc->setColumnWidth($COL_INITIATOR, 6);

$doc->setCell(2, $COL_FIRST_LINE, Spreadsheet::TYPE_TEXT, Yii::t('export', 'line'));
$doc->setColumnWidth($COL_FIRST_LINE, 3);

$doc->setCell(2, $COL_STATUS, Spreadsheet::TYPE_TEXT, Yii::t('export', 'status'));
$doc->setColumnWidth($COL_STATUS, 3);

$doc->setCell(2, $COL_CHANGE, Spreadsheet::TYPE_TEXT, Yii::t('export', 'amend_change'));
$doc->setColumnWidth($COL_CHANGE, 10);

$doc->setCell(2, $COL_REASON, Spreadsheet::TYPE_TEXT, Yii::t('export', 'amend_reason'));
$doc->setColumnWidth($COL_REASON, 10);

$doc->setCell(2, $COL_CONTACT, Spreadsheet::TYPE_TEXT, Yii::t('export', 'contact'));
$doc->setColumnWidth($COL_CONTACT, 6);

$doc->setCell(2, $COL_PROCEDURE, Spreadsheet::TYPE_TEXT, Yii::t('export', 'procedure'));
$doc->setColumnWidth($COL_PROCEDURE, 6);

if ($hasResponsibilities) {
    $doc->setCell(2, $COL_RESPONSIBILITY, Spreadsheet::TYPE_TEXT, Yii::t('export', 'responsibility'));
    $doc->setColumnWidth($COL_RESPONSIBILITY, 6);
}

$doc->drawBorder(1, $firstCol, 2, $LAST_COL, 1.5);


// Amendments

$row = 3;

foreach ($amendments as $amendmentGroup) {
    $motion = $amendmentGroup['motion'];
    if ($motion->getMyMotionType()->amendmentsOnly) {
        continue;
    }
    $doc->setMinRowHeight($row, 2);

    $row++;
    $maxRows        = 1;
    $firstMotionRow = $row;

    $initiatorNames = [];
    foreach ($motion->getInitiators() as $supp) {
        $initiatorNames[] = $supp->getNameWithResolutionDate(false);
    }

    $title = '<strong>' . $motion->getTitleWithPrefix() . '</strong>';
    $title .= ' (' . Yii::t('export', 'motion_by') . ': ' . Html::encode(implode(', ', $initiatorNames)) . ')';
    if ($hasAgendaItems && $motion->agendaItem) {
        $doc->setCell($row, $COL_AGENDA_ITEM, Spreadsheet::TYPE_TEXT, $motion->agendaItem->getShownCode(true));
    }
    $title = HTMLTools::correctHtmlErrors($title);
    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_HTML, $title, null, ['fo:wrap-option' => 'no-wrap']);

    if ($hasResponsibilities) {
        $responsibility = [];
        if ($motion->responsibilityUser) {
            $user = $motion->responsibilityUser;
            $responsibility[] = $user->name ? $user->name : $user->getAuthName();
        }
        if ($motion->responsibilityComment) {
            $responsibility[] = $motion->responsibilityComment;
        }
        $doc->setCell($row, $COL_RESPONSIBILITY, Spreadsheet::TYPE_TEXT, implode(', ', $responsibility));
    }

    foreach ($amendmentGroup['amendments'] as $amendment) {
        $row++;

        $initiatorNames   = [];
        $initiatorContacs = [];
        foreach ($amendment->getInitiators() as $supp) {
            $initiatorNames[] = $supp->getNameWithResolutionDate(false);
            if ($supp->contactEmail != '') {
                $initiatorContacs[] = $supp->contactEmail;
            }
            if ($supp->contactPhone != '') {
                $initiatorContacs[] = $supp->contactPhone;
            }
        }
        $firstLine = $amendment->getFirstDiffLine();

        if ($hasAgendaItems && $motion->agendaItem) {
            $doc->setCell($row, $COL_AGENDA_ITEM, Spreadsheet::TYPE_TEXT, $motion->agendaItem->getShownCode(true));
        }
        $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $amendment->getFormattedTitlePrefix());
        $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, implode(', ', $initiatorNames));
        $doc->setCell($row, $COL_CONTACT, Spreadsheet::TYPE_TEXT, implode(', ', $initiatorContacs));
        $doc->setCell($row, $COL_FIRST_LINE, Spreadsheet::TYPE_NUMBER, $firstLine);
        $doc->setCell($row, $COL_STATUS, Spreadsheet::TYPE_HTML, $amendment->getFormattedStatus());
        $changeExplanation = HTMLTools::correctHtmlErrors($amendment->changeExplanation);
        $doc->setCell($row, $COL_REASON, Spreadsheet::TYPE_HTML, $changeExplanation);

        $change = '';
        if ($amendment->changeEditorial != '') {
            $change .= '<h4>' . Yii::t('amend', 'editorial_hint') . '</h4><br>';
            $change .= $amendment->changeEditorial;
        }
        foreach ($amendment->getSortedSections(false) as $section) {
            $change .= $section->getSectionType()->getAmendmentODS();
        }
        $change = HTMLTools::correctHtmlErrors($change);
        $doc->setCell($row, $COL_CHANGE, Spreadsheet::TYPE_HTML, $change);

        $proposal = $amendment->getFormattedProposalStatus();
        if ($amendment->hasAlternativeProposaltext()) {
            $reference = $amendment->getMyProposalReference();
            /** @var AmendmentSection[] $sections */
            $sections = $reference->getSortedSections(false);
            foreach ($sections as $section) {
                $firstLine    = $section->getFirstLineNumber();
                $lineLength   = $section->getCachedConsultation()->getSettings()->lineLength;
                $originalData = $section->getOriginalMotionSection()?->getData() ?? '';
                $newData      = $section->getData();
                $proposal     .= TextSimpleCommon::formatAmendmentForOds($originalData, $newData, $firstLine, $lineLength);
            }
        }
        $doc->setCell($row, $COL_PROCEDURE, Spreadsheet::TYPE_HTML, $proposal);

        if ($hasResponsibilities) {
            $responsibility = [];
            if ($amendment->responsibilityUser) {
                $user             = $amendment->responsibilityUser;
                $responsibility[] = $user->name ? $user->name : $user->getAuthName();
            }
            if ($amendment->responsibilityComment) {
                $responsibility[] = $amendment->responsibilityComment;
            }
            $doc->setCell($row, $COL_RESPONSIBILITY, Spreadsheet::TYPE_TEXT, implode(', ', $responsibility));
        }
    }

    $doc->drawBorder($firstMotionRow, $firstCol, $row, $LAST_COL, 1.5);
    $row++;
}

try {
    echo $doc->finishAndGetDocument();
} catch (Exception $e) {
    if (in_array(YII_ENV, ['dev', 'test'])) {
        var_dump($e);
    } else {
        echo Yii::t('base', 'err_unknown');
    }
    die();
}
