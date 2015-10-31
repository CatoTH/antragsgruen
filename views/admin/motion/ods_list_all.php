<?php

use \app\components\opendocument\Spreadsheet;
use app\components\StringSplitter;
use app\models\db\Amendment;
use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;

/**
 * @var $this yii\web\View
 * @var array $items
 */

/** @var \app\controllers\Base $controller */
const agendaColor = '#fff2cc';
const motionColor = '#38761d';
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

$currCol = 0;

$COL_PREFIX     = $currCol++;
$COL_TITLE      = $currCol++;
$COL_INITIATOR  = $currCol++;
$COL_EMAIL      = $currCol++;
$COL_PHONE      = $currCol++;

$colLimit = $currCol;

$doc->setCell(0, $COL_PREFIX, Spreadsheet::TYPE_HTML, 'Kürzel');
$doc->setCell(0, $COL_TITLE, Spreadsheet::TYPE_HTML, 'Titel');
$doc->setCell(0, $COL_INITIATOR, Spreadsheet::TYPE_HTML, 'Antragsteller*in');
$doc->setCell(0, $COL_EMAIL, Spreadsheet::TYPE_HTML, 'Email-Adresse');
$doc->setCell(0, $COL_PHONE, Spreadsheet::TYPE_HTML, 'Telefonnummer');

$doc->setColumnWidth($COL_PREFIX,2);
$doc->setColumnWidth($COL_TITLE,6);
$doc->setColumnWidth($COL_INITIATOR,6);
$doc->setColumnWidth($COL_EMAIL,4);
$doc->setColumnWidth($COL_PHONE,4);

$row = 1;

$fill = function ($cellAttributes,$textAttributes) use ($doc,&$row,$colLimit) {
    for ($col = 0; $col < $colLimit; $col++)
        $doc->setCellStyle($row, $col,$cellAttributes,$textAttributes);
};

foreach ($items as $item) {
    if ($item instanceof ConsultationAgendaItem) {
        $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_HTML, $item->code);
        $fill (['fo:background-color' => agendaColor],[]);
    }
    else if ($item instanceof Motion || $item instanceof Amendment) {
        $title = $item->title;
        $prefix = $item->titlePrefix;
        $initiator = $item->getInitiators() [0];
        $email = $initiator->contactEmail;
        $phone = $initiator->contactPhone;
        $name = $initiator->getNameWithOrga();
        $firstName = StringSplitter::first ([' '],substr($name,0,4) == 'Dr. ' ? substr($name,4) : $name);
        if ($item instanceof Motion) {
            $doc->setCell($row, $COL_TITLE, Spreadsheet::TYPE_HTML, $item->title);
            $fill ([], ['fo:color' => motionColor]);
        }
        $doc->setCell($row, $COL_PREFIX   , Spreadsheet::TYPE_HTML, $prefix);
        $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_HTML, $name);
        $doc->setCell($row, $COL_EMAIL    , Spreadsheet::TYPE_LINK, ['href' => 'mailto:' . $email . '?subject=' . $prefix . ': ' . $title . '&body=Hallo ' . $firstName . ',%0D%0A%0D%0Aich schreibe wegen Deines Antrags ' . $prefix . ' ("' . $title . '"), für den ich in der Antragskommission zuständig bin.','text' => $email]);
        if ($phone)
            $doc->setCell($row, $COL_PHONE    , Spreadsheet::TYPE_LINK, ['href' => 'tel:' . StringSplitter::first (["//",","],$phone), 'text' => $phone]);
    }
    else { // null
        $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_HTML, "Sonstiges");
        $fill (['fo:background-color' => agendaColor],[]);
    }
    $row++;
}

$content = $doc->create();

if ($DEBUG) {
    $doc->debugOutput();
}

$zip->deleteName('content.xml');
$zip->addFromString('content.xml', $content);
$zip->close();

readfile($tmpZipFile);
unlink($tmpZipFile);
