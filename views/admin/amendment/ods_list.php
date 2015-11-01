<?php

use app\models\db\Motion;
use \app\components\opendocument\Spreadsheet;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var Motion[] $motions
 * @var bool $textCombined
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;

$DEBUG = false;

/** @var \app\models\settings\AntragsgruenApp $params */
$params = \yii::$app->params;

$tmpZipFile   = $params->tmpDir . uniqid('zip-');
$templateFile = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'OpenOffice-Template.ods';
copy($templateFile, $tmpZipFile);

$zip = new ZipArchive();
if ($zip->open($tmpZipFile) !== true) {
    die("cannot open <$tmpZipFile>\n");
}

$content = $zip->getFromName('content.xml');
$doc     = new Spreadsheet($content);


$currCol = $firstCol = 1;

$COL_PREFIX     = $currCol++;
$COL_INITIATOR  = $currCol++;
$COL_FIRST_LINE = $currCol++;
$COL_CHANGE     = $currCol++;
$COL_REASON     = $currCol++;
$COL_CONTACT    = $currCol++;
$COL_PROCEDURE  = $currCol++;


// Title

$doc->setCell(1, $firstCol, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'amendments'));
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

$doc->setCell(2, $COL_FIRST_LINE, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'line'));
$doc->setColumnWidth($COL_FIRST_LINE, 3);

$doc->setCell(2, $COL_CHANGE, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'amend_change'));
$doc->setColumnWidth($COL_CHANGE, 10);

$doc->setCell(2, $COL_REASON, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'amend_reason'));
$doc->setColumnWidth($COL_REASON, 10);

$doc->setCell(2, $COL_CONTACT, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'contact'));
$doc->setColumnWidth($COL_CONTACT, 6);

$doc->setCell(2, $COL_PROCEDURE, Spreadsheet::TYPE_TEXT, \Yii::t('export', 'procedure'));
$doc->setColumnWidth($COL_PROCEDURE, 6);

$doc->drawBorder(1, $firstCol, 2, $COL_PROCEDURE, 1.5);


// Amendments

$row = 3;

foreach ($motions as $motion) {
    $doc->setMinRowHeight($row, 2);

    $row++;
    $maxRows        = 1;
    $firstMotionRow = $row;

    $initiatorNames = [];
    foreach ($motion->getInitiators() as $supp) {
        $initiatorNames[] = $supp->getNameWithResolutionDate(false);
    }

    $title = '<strong>' . $motion->getTitleWithPrefix() . '</strong>';
    $title .= ' (von: ' . Html::encode(implode(', ', $initiatorNames)) . ')';
    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_HTML, $title, null, ['fo:wrap-option' => 'no-wrap']);

    $amendments = $motion->getVisibleAmendmentsSorted();
    foreach ($amendments as $amendment) {
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

        $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $amendment->titlePrefix);
        $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, implode(', ', $initiatorNames));
        $doc->setCell($row, $COL_CONTACT, Spreadsheet::TYPE_TEXT, implode(', ', $initiatorContacs));
        $doc->setCell($row, $COL_FIRST_LINE, Spreadsheet::TYPE_NUMBER, $firstLine);
        $doc->setCell($row, $COL_REASON, Spreadsheet::TYPE_HTML, $amendment->changeExplanation);

        $change = '';
        if ($amendment->changeEditorial != '') {
            $change .= '<h4>' . \Yii::t('amend', 'editorial_hint') . '</h4><br>';
            $change .= $amendment->changeEditorial;
        }
        foreach ($amendment->getSortedSections(false) as $section) {
            $change .= $section->getSectionType()->getAmendmentODS();
        }
        $doc->setCell($row, $COL_CHANGE, Spreadsheet::TYPE_HTML, $change);
    }

    $doc->drawBorder($firstMotionRow, $firstCol, $row, $COL_PROCEDURE, 1.5);
    $row++;
}

try {
    $content = $doc->create();
} catch (\Exception $e) {
    if (in_array(YII_ENV, ['dev', 'test'])) {
        var_dump($e);
    } else {
        echo \Yii::t('base', 'err_unknown');
    }
    die();
}
if ($DEBUG) {
    $doc->debugOutput();
}

$zip->deleteName('content.xml');
$zip->addFromString('content.xml', $content);
$zip->close();

readfile($tmpZipFile);
unlink($tmpZipFile);
