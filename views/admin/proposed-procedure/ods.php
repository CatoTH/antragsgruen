<?php

use app\components\ProposedProcedureAgenda;
use app\models\db\AmendmentSection;
use app\models\db\Consultation;
use app\models\sectionTypes\TextSimple;
use CatoTH\HTML2OpenDocument\Spreadsheet;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var ProposedProcedureAgenda[] $proposedAgenda
 * @var Consultation $consultation
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

$currCol = $firstCol = 0;

$COL_PREFIX    = $currCol++;
$COL_INITIATOR = $currCol++;
$COL_PROCEDURE = $currCol++;


// Title

$titleStyles = ['fo:font-family' => 'Arvo Gruen', 'fo:font-size' => '18pt', 'fo:font-weight' => 'normal'];
$doc->setCell(0, 0, Spreadsheet::TYPE_TEXT, $consultation->site->organization);
$doc->setMinRowHeight(0, 1.8);
$doc->setCellStyle(0, 0, [], $titleStyles);

$row       = 0;
$titleRows = explode("\n", $consultation->getSettings()->pdfIntroduction);
foreach ($titleRows as $titleRow) {
    if (trim($titleRow) !== '' && $row < 7) {
        $row++;
        $doc->setCell($row, 0, Spreadsheet::TYPE_TEXT, $titleRow);
        $doc->setMinRowHeight($row, 1.8);
        $doc->setCellStyle($row, 0, [], $titleStyles);
    }
}

$doc->setCell(7, $firstCol, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'pp_title'));
$doc->setMinRowHeight(7, 1.8);
$doc->setCellStyle(7, $firstCol, [], [
    'fo:font-family' => 'Arvo Gruen',
    'fo:font-size'   => '18pt',
    'fo:font-weight' => 'normal',
]);


// Heading

$headerStyle = ['fo:font-family' => 'PT Sans', 'fo:font-size' => '11pt', 'fo:font-weight' => 'bold'];
$doc->setCell(9, $COL_PREFIX, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'prefix_short'));
$doc->setCellStyle(9, $COL_PREFIX, [], $headerStyle);
$doc->setColumnWidth($COL_INITIATOR, 2);

$doc->setCell(9, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'initiator'));
$doc->setCellStyle(9, $COL_INITIATOR, [], $headerStyle);
$doc->setColumnWidth($COL_INITIATOR, 4.9);

$doc->setCell(9, $COL_PROCEDURE, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'procedure'));
$doc->setCellStyle(9, $COL_PROCEDURE, [], $headerStyle);
$doc->setColumnWidth($COL_PROCEDURE, 19);

$doc->setMinRowHeight(9, 1.1);

$doc->drawBorder(9, $firstCol, 9, $COL_PROCEDURE, 0.5);


$printAmendment = function (Spreadsheet $doc, \app\models\db\Amendment $amendment, $row)
use ($COL_PREFIX, $COL_INITIATOR, $COL_PROCEDURE) {
    $cellStyle = ['fo:font-family' => 'PT Sans', 'fo:font-size' => '10pt', 'fo:font-weight' => 'normal'];
    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $amendment->titlePrefix);
    $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, $amendment->getInitiatorsStr());
    $doc->setCellStyle($row, $COL_PREFIX, [], $cellStyle);
    $doc->setCellStyle($row, $COL_INITIATOR, [], $cellStyle);
    $doc->setCellStyle($row, $COL_PROCEDURE, [], $cellStyle);

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
    $cellStyle = ['fo:font-family' => 'PT Sans', 'fo:font-size' => '10pt', 'fo:font-weight' => 'normal'];
    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $motion->titlePrefix);
    $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, $motion->getInitiatorsStr());
    $doc->setCellStyle($row, $COL_PREFIX, [], $cellStyle);
    $doc->setCellStyle($row, $COL_INITIATOR, [], $cellStyle);
    $doc->setCellStyle($row, $COL_PROCEDURE, [], $cellStyle);

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

$row = 10;


foreach ($proposedAgenda as $proposedItem) {
    foreach ($proposedItem->votingBlocks as $votingBlock) {
        $doc->setMinRowHeight($row, 1);

        $row++;
        $maxRows        = 1;
        $firstAgendaRow = $row;

        $styles = [
            'fo:wrap-option' => 'no-wrap',
            'fo:font-family' => 'PT Sans',
            'fo:font-size'   => '10pt',
            'fo:font-weight' => '900'
        ];
        $title  = $proposedItem->title . ': ' . $votingBlock->title;
        $doc->setCellStyle($row, $COL_PREFIX, null, $styles);
        $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $title, null, $styles);
        $doc->setMinRowHeight($row, 1.2);

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

        $doc->drawBorder($firstAgendaRow, $firstCol, $row, $COL_PROCEDURE, 0.5);
        $row++;
    }
}

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
