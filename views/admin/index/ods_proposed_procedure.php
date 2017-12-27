<?php

use app\models\db\AmendmentSection;
use app\models\sectionTypes\TextSimple;
use CatoTH\HTML2OpenDocument\Spreadsheet;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var \app\components\ProposedProcedureAgenda[] $proposedAgenda
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

$COL_PREFIX    = $currCol++;
$COL_INITIATOR = $currCol++;
$COL_PROCEDURE = $currCol++;


// Title

$doc->setCell(1, $firstCol, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'pp_title'));
$doc->setCellStyle(1, $firstCol, [], [
    'fo:font-size'   => '16pt',
    'fo:font-weight' => 'bold',
]);
$doc->setMinRowHeight(1, 1.5);


// Heading

$doc->setCell(2, $COL_PREFIX, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'prefix_short'));
$doc->setCellStyle(2, $COL_PREFIX, [], ['fo:font-weight' => 'bold']);

$doc->setCell(2, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'initiator'));
$doc->setColumnWidth($COL_INITIATOR, 6);

$doc->setCell(2, $COL_PROCEDURE, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'procedure'));
$doc->setColumnWidth($COL_PROCEDURE, 12);

$doc->drawBorder(1, $firstCol, 2, $COL_PROCEDURE, 1.5);


$printAmendment = function (Spreadsheet $doc, \app\models\db\Amendment $amendment, $row)
use ($COL_PREFIX, $COL_INITIATOR, $COL_PROCEDURE) {
    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $amendment->titlePrefix);
    $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, $amendment->getInitiatorsStr());

    $minHeight = 1;
    $proposal  = '<p>' . $amendment->getFormattedProposalStatus() . '</p>';

    if ($amendment->hasAlternativeProposaltext()) {
        $reference = $amendment->proposalReference;
        /** @var AmendmentSection[] $sections */
        $sections = $reference->getSortedSections(false);
        foreach ($sections as $section) {
            $firstLine    = $section->getFirstLineNumber();
            $lineLength   = $section->getCachedConsultation()->getSettings()->lineLength;
            $originalData = $section->getOriginalMotionSection()->data;
            $newData      = $section->data;
            $proposal     .= TextSimple::formatAmendmentForOds($originalData, $newData, $firstLine, $lineLength);
            $minHeight    += 1;
        }
    }
    if ($amendment->proposalExplanation) {
        $minHeight += 1;
        $proposal  .= '<p>' . Html::encode($amendment->proposalExplanation) . '</p>';
    }

    $doc->setCell($row, $COL_PROCEDURE, Spreadsheet::TYPE_HTML, $proposal);
    $doc->setMinRowHeight($row, $minHeight);
};


$printMotion = function (Spreadsheet $doc, \app\models\db\Motion $motion, $row)
use ($COL_PREFIX, $COL_INITIATOR, $COL_PROCEDURE) {
    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $motion->titlePrefix);
    $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, $motion->getInitiatorsStr());

    $minHeight = 1;
    $proposal  = '<p>' . $motion->getFormattedProposalStatus() . '</p>';
    if ($motion->proposalExplanation) {
        $minHeight += 1;
        $proposal  .= '<p>' . Html::encode($motion->proposalExplanation) . '</p>';
    }

    $doc->setCell($row, $COL_PROCEDURE, Spreadsheet::TYPE_HTML, $proposal);
    $doc->setMinRowHeight($row, $minHeight);
};


// Procedure

$row = 3;


foreach ($proposedAgenda as $proposedItem) {
    foreach ($proposedItem->votingBlocks as $votingBlock) {
        $doc->setMinRowHeight($row, 2);

        $row++;
        $maxRows        = 1;
        $firstAgendaRow = $row;

        $styles = ['fo:wrap-option' => 'no-wrap', 'fo:font-size' => '12pt', 'fo:font-weight' => 'bold'];
        $title  = $proposedItem->title . ': ' . $votingBlock->title;
        $doc->setCellStyle($row, $COL_PREFIX, null, $styles);
        $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $title, null, $styles);
        $doc->setMinRowHeight($row, 1.4);

        foreach ($votingBlock->items as $item) {
            $row++;
            if (is_a($item, \app\models\db\Amendment::class)) {
                /** @var \app\models\db\Amendment $item */
                $printAmendment($doc, $item, $row);
            } else {
                /** @var \app\models\db\Motion $item */
                $printMotion($doc, $item, $row);
            }
        }

        $doc->drawBorder($firstAgendaRow, $firstCol, $row, $COL_PROCEDURE, 1.5);
        $row++;
    }
}

/*
$handledMotions = [];


$agendaItems = \app\models\db\ConsultationAgendaItem::getSortedFromConsultation($consultation);
foreach ($agendaItems as $agendaItem) {
    $hasAmendments = false;
    foreach ($agendaItem->getVisibleMotions($withdrawn) as $motion) {
        if (count($motion->getVisibleAmendments($withdrawn)) > 0) {
            $hasAmendments = true;
        }
        $handledMotions[] = $motion->id;
    }
    if (!$hasAmendments) {
        continue;
    }

    $doc->setMinRowHeight($row, 2);

    $row++;
    $maxRows        = 1;
    $firstAgendaRow = $row;

    $styles = ['fo:wrap-option' => 'no-wrap', 'fo:font-size' => '12pt', 'fo:font-weight' => 'bold'];
    $title  = $agendaItem->getShownCode(true) . ' ' . $agendaItem->title;
    $doc->setCellStyle($row, $COL_PREFIX, null, $styles);
    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $title, null, $styles);
    $doc->setMinRowHeight($row, 1.4);

    foreach ($agendaItem->getVisibleMotions($withdrawn) as $motion) {
        $amendments = $motion->getVisibleAmendments($withdrawn);
        if (count($amendments) === 0) {
            continue;
        }

        $initiatorNames = [];
        foreach ($motion->getInitiators() as $supp) {
            $initiatorNames[] = $supp->getNameWithResolutionDate(false);
        }

        $row++;
        $title = '<strong>' . Html::encode($motion->getTitleWithPrefix()) . '</strong>';
        $title .= ' (' . \Yii::t('export', 'motion_by') . ': ' . Html::encode(implode(', ', $initiatorNames)) . ')';
        $title = HTMLTools::correctHtmlErrors($title);
        $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_HTML, $title, null, ['fo:wrap-option' => 'no-wrap']);
        foreach ($amendments as $amendment) {
            $row++;
            $printAmendment($doc, $amendment, $row);
        }
    }

    $doc->drawBorder($firstAgendaRow, $firstCol, $row, $COL_PROCEDURE, 1.5);
    $row++;
}


// Output the motions that are not assigned to an agenda item

foreach ($motions as $motion) {
    $amendments = $motion->getVisibleAmendmentsSorted($withdrawn);
    if (in_array($motion->id, $handledMotions) || count($amendments) === 0) {
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
    $title .= ' (' . \Yii::t('export', 'motion_by') . ': ' . Html::encode(implode(', ', $initiatorNames)) . ')';
    $title = HTMLTools::correctHtmlErrors($title);
    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_HTML, $title, null, ['fo:wrap-option' => 'no-wrap']);

    foreach ($amendments as $amendment) {
        $row++;
        $printAmendment($doc, $amendment, $row);
    }

    $doc->drawBorder($firstMotionRow, $firstCol, $row, $COL_PROCEDURE, 1.5);
    $row++;
}
*/


try {
    echo $doc->finishAndGetDocument();
} catch (\Exception $e) {
    if (in_array(YII_ENV, ['dev', 'test'])) {
        var_dump($e);
    } else {
        echo \Yii::t('base', 'err_unknown');
    }
    die();
}
