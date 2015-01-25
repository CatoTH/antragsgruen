<?php
/**
 * @var IndexController $this
 * @var array $antraege
 * @var bool $text_begruendung_zusammen
 * @var bool $antraege_separat
 * @var bool $zeilennummer_separat
 */

$curr_col = ord("B");

$first_col = chr($curr_col);
if ($antraege_separat) $COL_ANTRAGS_NR = chr($curr_col++);
$COL_AE_NR           = chr($curr_col++);
$COL_ANTRAGSTELLERIN = chr($curr_col++);
$COL_ZEILE           = chr($curr_col++);
$COL_ANTRAGSTEXT     = chr($curr_col++);
if (!$text_begruendung_zusammen) $COL_BEGRUENDUNG = chr($curr_col++);
$COL_KONTAKT   = chr($curr_col++);
$COL_VERFAHREN = chr($curr_col++);

$tmpZipFile = "/tmp/" . uniqid("zip-");
copy(Yii::app()->params['ods_default_template'], $tmpZipFile);

$zip = new ZipArchive();
if ($zip->open($tmpZipFile) !== TRUE) {
    die("cannot open <$tmpZipFile>\n");
}

$content = $zip->getFromName('content.xml');

$DEBUG = false;

if ($DEBUG) {
    echo "<pre>";
} else {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename=aenderungsantraege.xlsx');
    header('Cache-Control: max-age=0');
}

$doc = new OdsTemplateEngine($content);
$doc->setCell(1, 1, OdsTemplateEngine::$TYPE_TEXT, "Test", null, null);
$doc->setCell(1, 2, OdsTemplateEngine::$TYPE_NUMBER, 2, null, null);

$content = $doc->create();

if ($DEBUG) $doc->debugOutput();

$zip->deleteName("content.xml");
$zip->addFromString("content.xml", $content);
$zip->close();


Header("Content-Type: application/vnd.oasis.opendocument.spreadsheet");
header('Content-disposition: filename="' . addslashes('Änderungsanträge.ods') . '"');

readfile($tmpZipFile);
unlink($tmpZipFile);
