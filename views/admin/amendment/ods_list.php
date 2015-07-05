<?php

use app\models\db\Motion;
use \app\components\opendocument\Spreadsheet;

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

$tmpZipFile   = $params->tmpDir . uniqid("zip-");
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
$COL_TITLE      = $currCol++;
$COL_CHANGE     = $currCol++;
$COL_REASON     = $currCol++;
$COL_CONTACT    = $currCol++;
$COL_PROCEDURE  = $currCol++;


// Title

$doc->setCell(1, $firstCol, Spreadsheet::TYPE_TEXT, 'Änderungsanträge');
$doc->setCellStyle(1, $firstCol, [], [
    'fo:font-size'   => '16pt',
    'fo:font-weight' => 'bold',
]);
$doc->setMinRowHeight(1, 1.5);


// Heading

$doc->setCell(2, $COL_PREFIX, Spreadsheet::TYPE_TEXT, 'ÄA-Nr.');
$doc->setCellStyle(2, $COL_PREFIX, [], ["fo:font-weight" => "bold"]);

$doc->setCell(2, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, 'AntragstellerIn');
$doc->setColumnWidth($COL_INITIATOR, 6);

$doc->setCell(2, $COL_FIRST_LINE, Spreadsheet::TYPE_TEXT, 'Zeile');
$doc->setColumnWidth($COL_FIRST_LINE, 3);

$doc->setCell(2, $COL_TITLE, Spreadsheet::TYPE_TEXT, 'Titel');
$doc->setColumnWidth($COL_TITLE, 6);

$doc->setCell(2, $COL_CHANGE, Spreadsheet::TYPE_TEXT, 'Änderung');
$doc->setColumnWidth($COL_CHANGE, 10);

$doc->setCell(2, $COL_REASON, Spreadsheet::TYPE_TEXT, 'Begründung');
$doc->setColumnWidth($COL_REASON, 10);

$doc->setCell(2, $COL_CONTACT, Spreadsheet::TYPE_TEXT, 'Kontakt');
$doc->setColumnWidth($COL_CONTACT, 6);

$doc->setCell(2, $COL_PROCEDURE, Spreadsheet::TYPE_TEXT, 'Verfahren');
$doc->setColumnWidth($COL_PROCEDURE, 6);

$doc->drawBorder(1, $firstCol, 2, $COL_PROCEDURE, 1.5);


/*
// Motions

$row = 2;

foreach ($motions as $motion) {
    $row++;
    $maxRows = 1;

    $initiatorNames   = [];
    $initiatorContacs = [];
    foreach ($motion->getInitiators() as $supp) {
        $initiatorNames[] = $supp->getNameWithResolutionDate(false);
        if ($supp->contactEmail != '') {
            $initiatorContacs[] = $supp->contactEmail;
        }
        if ($supp->contactPhone != '') {
            $initiatorContacs[] = $supp->contactPhone;
        }
    }

    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $motion->titlePrefix);
    $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, implode(', ', $initiatorNames));
    $doc->setCell($row, $COL_CONTACT, Spreadsheet::TYPE_TEXT, implode("\n", $initiatorContacs));

    if ($textCombined) {
        $text = '';
        foreach ($motion->getSortedSections(true) as $section) {
            $text .= $section->consultationSetting->title . "\n\n";
            $text .= $section->getSectionType()->getMotionODS();
            $text .= "\n\n";
        }
        $doc->setCell($row, $COL_TEXTS[0], Spreadsheet::TYPE_HTML, $text);
    } else {
        foreach ($motionType->motionSections as $section) {
            $text = '';
            foreach ($motion->sections as $sect) {
                if ($sect->sectionId == $section->id) {
                    $text = $sect->getSectionType()->getMotionODS();
                }
            }
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
*/

$content = $doc->create();

if ($DEBUG) {
    $doc->debugOutput();
}

$zip->deleteName('content.xml');
$zip->addFromString('content.xml', $content);
$zip->close();

readfile($tmpZipFile);
unlink($tmpZipFile);
