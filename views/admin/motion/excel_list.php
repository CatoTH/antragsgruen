<?php

use app\models\db\ConsultationMotionType;
use app\models\db\Motion;

/**
 * @var $this yii\web\View
 * @var Motion[] $motions
 * @var bool $textCombined
 * @var ConsultationMotionType $motionType
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;

/*
foreach ($antraege as $ant) {
	echo $ant["antrag"]->revision_name . "<br>";
	foreach ($ant["aes"] as $ae) echo "- " . $ae->revision_name . "<br>";
}
*/

PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);


$hasTags = ($consultation->tags > 0);


$currCol   = ord("B");
$first_col = chr($currCol);

$COL_PREFIX    = chr($currCol++);
$COL_INITIATOR = chr($currCol++);
$COL_TITLE     = chr($currCol++);
$COL_TEXTS     = [];
if ($textCombined) {
    $COL_TEXTS[] = chr($currCol++);
} else {
    foreach ($motionType->motionSections as $section) {
        $COL_TEXTS[$section->id] = chr($currCol++);
    }
}
if ($hasTags) {
    $COL_TAGS = chr($currCol++);
}
$COL_CONTACT   = chr($currCol++);
$COL_PROCEDURE = chr($currCol++);

$objPHPExcel = new \PHPExcel();

$objPHPExcel->getProperties()->setCreator("Antragsgruen.de");
$objPHPExcel->getProperties()->setLastModifiedBy("Antragsgruen.de");
$objPHPExcel->getProperties()->setTitle($consultation->title);
$objPHPExcel->getProperties()->setSubject('Motions');
$objPHPExcel->getProperties()->setDescription($consultation->title . ' - ' . 'Motions');

$objPHPExcel->setActiveSheetIndex(0);

$objPHPExcel->getActiveSheet()->SetCellValue($COL_PREFIX . '2', 'Motions');
$objPHPExcel->getActiveSheet()->getStyle($COL_PREFIX . "2")->applyFromArray([
    "font" => [
        "bold" => true
    ]
]);

$objPHPExcel->getActiveSheet()->SetCellValue($COL_PREFIX . '3', 'Antragsnr.');
$objPHPExcel->getActiveSheet()->SetCellValue($COL_INITIATOR . '3', 'AntragstellerIn');
$objPHPExcel->getActiveSheet()->SetCellValue($COL_TITLE . '3', 'Titel');
if ($textCombined) {
    $objPHPExcel->getActiveSheet()->SetCellValue($COL_TEXTS[0] . '3', 'Text');
} else {
    foreach ($motionType->motionSections as $section) {
        $objPHPExcel->getActiveSheet()->SetCellValue($COL_TEXTS[$section->id] . '3', $section->title);
        $COL_TEXTS[$section->id] = chr($currCol++);
    }
}
if (isset($COL_TAGS)) {
    $objPHPExcel->getActiveSheet()->SetCellValue($COL_TAGS . '3', 'Schlagworte');
}
$objPHPExcel->getActiveSheet()->SetCellValue($COL_CONTACT . '3', 'Kontakt');
$objPHPExcel->getActiveSheet()->SetCellValue($COL_PROCEDURE . '3', 'Verfahren');
$objPHPExcel->getActiveSheet()->getStyle($COL_PREFIX . "3:" . $COL_PROCEDURE . "3")->applyFromArray([
    "font" => [
        "bold" => true
    ]
]);

$styleThinBlackBorderOutline = [
    'borders' => [
        'outline' => [
            'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];
$objPHPExcel->getActiveSheet()->getStyle($COL_PREFIX . '2:' . $COL_PROCEDURE . '3')
    ->applyFromArray($styleThinBlackBorderOutline);


PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());


$row = 3;

foreach ($motions as $motion) {
    $row++;

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

    $objPHPExcel->getActiveSheet()->SetCellValue($COL_PREFIX . $row, $motion->titlePrefix);
    $objPHPExcel->getActiveSheet()->SetCellValue($COL_INITIATOR . $row, implode(", ", $initiatorNames));
    $objPHPExcel->getActiveSheet()->SetCellValue($COL_TITLE . $row, $motion->title);
    $objPHPExcel->getActiveSheet()->SetCellValue($COL_CONTACT . $row, implode("\n", $initiatorContacs));

    /*
    $text_antrag   = str_replace(array("[QUOTE]", "[/QUOTE]"), array("\n\n", "\n\n"), $antrag->text);
    $text_antrag   = HtmlBBcodeUtils::removeBBCode($text_antrag);
    $text_antrag   = HtmlBBcodeUtils::text2zeilen(trim($text_antrag), 120, true);
    $zeilen_antrag = [];
    foreach ($text_antrag as $t) {
        $x             = explode("\n", $t);
        $zeilen_antrag = array_merge($zeilen_antrag, $x);
    }

    $text2_antrag   = str_replace(array("[QUOTE]", "[/QUOTE]"), array("\n\n", "\n\n"), $antrag->text2);
    $text2_antrag   = HtmlBBcodeUtils::removeBBCode($text2_antrag);
    $text2_antrag   = HtmlBBcodeUtils::text2zeilen(trim($text2_antrag), 120, true);
    $zeilen2_antrag = [];
    foreach ($text2_antrag as $t) {
        $x              = explode("\n", $t);
        $zeilen2_antrag = array_merge($zeilen2_antrag, $x);
    }

    $text_begruendung   = str_replace(array("[QUOTE]", "[/QUOTE]"), array("\n\n", "\n\n"), $antrag->begruendung);
    $text_begruendung   = HtmlBBcodeUtils::removeBBCode($text_begruendung);
    $text_begruendung   = HtmlBBcodeUtils::text2zeilen(trim($text_begruendung), 120, true);
    $zeilen_begruendung = [];
    foreach ($text_begruendung as $t) {
        $x                  = explode("\n", $t);
        $zeilen_begruendung = array_merge($zeilen_begruendung, $x);
    }

    if ($text_begruendung_zusammen) {
        $text1name       = veranstaltungsspezifisch_text1_name($this->veranstaltung, $antrag->typ);
        $text2name       = veranstaltungsspezifisch_text2_name($this->veranstaltung, $antrag->typ);
        $begruendungname = veranstaltungsspezifisch_begruendung_name($this->veranstaltung, $antrag->typ);
        $zeilen          = [];
        if (count($zeilen2_antrag) > 0) {
            $zeilen = array_merge($zeilen, array($text2name . ":"), $zeilen2_antrag, array("", ""));
        }
        $zeilen = array_merge($zeilen, array($text1name . ":"), $zeilen_antrag, array("", "", $begruendungname . ":"), $zeilen_begruendung);
        $objPHPExcel->getActiveSheet()->SetCellValue($COL_ANTRAGSTEXT . $row, trim(implode("\n", $zeilen)));
        $objPHPExcel->getActiveSheet()->getStyle($COL_ANTRAGSTEXT . $row)->getAlignment()->setWrapText(true);
        $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(14 * count($zeilen));
    } else {
        $maxlines = 0;

        if (isset($COL_ANTRAGSTEXT2)) {
            $objPHPExcel->getActiveSheet()->SetCellValue($COL_ANTRAGSTEXT2 . $row, trim(implode("\n", $zeilen2_antrag)));
            $objPHPExcel->getActiveSheet()->getStyle($COL_ANTRAGSTEXT2 . $row)->getAlignment()->setWrapText(true);
            if (count($zeilen2_antrag) > $maxlines) {
                $maxlines = count($zeilen2_antrag);
            }
        }

        $objPHPExcel->getActiveSheet()->SetCellValue($COL_ANTRAGSTEXT . $row, trim(implode("\n", $zeilen_antrag)));
        $objPHPExcel->getActiveSheet()->getStyle($COL_ANTRAGSTEXT . $row)->getAlignment()->setWrapText(true);
        if (count($zeilen_antrag) > $maxlines) {
            $maxlines = count($zeilen_antrag);
        }

        $objPHPExcel->getActiveSheet()->SetCellValue($COL_BEGRUENDUNG . $row, trim(implode("\n", $zeilen_begruendung)));
        $objPHPExcel->getActiveSheet()->getStyle($COL_BEGRUENDUNG . $row)->getAlignment()->setWrapText(true);
        if (count($zeilen_begruendung) > $maxlines) {
            $maxlines = count($zeilen_begruendung);
        }

        $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(14 * $maxlines);
    }
    */

    if (isset($COL_TAGS)) {
        $tags = [];
        foreach ($motion->tags as $tag) {
            $tags[] = $tag->name;
        }
        $objPHPExcel->getActiveSheet()->SetCellValue($COL_TAGS . $row, implode("\n", $tags));
    }
}

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(3);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_PREFIX)->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_INITIATOR)->setWidth(24);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_TITLE)->setWidth(40);
/*
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_ANTRAGSTEXT)->setAutoSize(80);
if (isset($COL_ANTRAGSTEXT2)) {
    $objPHPExcel->getActiveSheet()->getColumnDimension($COL_ANTRAGSTEXT2)->setAutoSize(80);
}
if (!$text_begruendung_zusammen) {
    $objPHPExcel->getActiveSheet()->getColumnDimension($COL_BEGRUENDUNG)->setAutoSize(80);
}
*/
if (isset($COL_TAGS)) {
    $objPHPExcel->getActiveSheet()->getColumnDimension($COL_TAGS)->setAutoSize(18);
}
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_CONTACT)->setWidth(24);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_PROCEDURE)->setWidth(13);


$objPHPExcel->getActiveSheet()->setTitle('Anträge');

// Save Excel 2007 file
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
$objWriter->save("php://output");
