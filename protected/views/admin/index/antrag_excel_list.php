<?php
/**
 * @var IndexController $this
 * @var array $antraege
 * @var bool $text_begruendung_zusammen
 */

/*
foreach ($antraege as $ant) {
	echo $ant["antrag"]->revision_name . "<br>";
	foreach ($ant["aes"] as $ae) echo "- " . $ae->revision_name . "<br>";
}
*/

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename=antraege.xlsx');
header('Cache-Control: max-age=0');


define('PCLZIP_TEMPORARY_DIR', '/tmp/');
PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);


$hat_tags = ($this->veranstaltung->tags > 0);

$curr_col = ord("B");
$first_col = chr($curr_col);

$COL_ANTRAGSNR       = chr($curr_col++);
$COL_ANTRAGSTELLERIN = chr($curr_col++);
$COL_TITEL           = chr($curr_col++);
if ($this->veranstaltung->veranstaltungsreihe->subdomain == 'wiesbaden') {
	$COL_ANTRAGSTEXT2     = chr($curr_col++);
}
$COL_ANTRAGSTEXT     = chr($curr_col++);
if (!$text_begruendung_zusammen) $COL_BEGRUENDUNG = chr($curr_col++);
if ($hat_tags) $COL_TAGS = chr($curr_col++);
$COL_KONTAKT   = chr($curr_col++);
$COL_VERFAHREN = chr($curr_col++);



$objPHPExcel = new PHPExcel();

$objPHPExcel->getProperties()->setCreator("Antragsgruen.de");
$objPHPExcel->getProperties()->setLastModifiedBy("Antragsgruen.de");
$objPHPExcel->getProperties()->setTitle($this->veranstaltung->name);
$objPHPExcel->getProperties()->setSubject("Anträge");
$objPHPExcel->getProperties()->setDescription($this->veranstaltung->name . " - Anträge");


$objPHPExcel->setActiveSheetIndex(0);

$objPHPExcel->getActiveSheet()->SetCellValue($COL_ANTRAGSNR . '2', "Antragsübersicht");
$objPHPExcel->getActiveSheet()->getStyle($COL_ANTRAGSNR . "2")->applyFromArray(array(
	"font" => array(
		"bold" => true
	)
));

$objPHPExcel->getActiveSheet()->SetCellValue($COL_ANTRAGSNR . '3', 'Antragsnr.');
$objPHPExcel->getActiveSheet()->SetCellValue($COL_ANTRAGSTELLERIN . '3', 'AntragstellerIn');
$objPHPExcel->getActiveSheet()->SetCellValue($COL_TITEL . '3', 'Titel');
if ($text_begruendung_zusammen) {
	$objPHPExcel->getActiveSheet()->SetCellValue($COL_ANTRAGSTEXT . '3', 'Antragstext u. Begründung');
}  else {
	if (isset($COL_ANTRAGSTEXT2)) {
		$objPHPExcel->getActiveSheet()->SetCellValue($COL_ANTRAGSTEXT2 . '3', 'Grüne Ziele');
	}
	$objPHPExcel->getActiveSheet()->SetCellValue($COL_ANTRAGSTEXT . '3', 'Antragstext');
	$objPHPExcel->getActiveSheet()->SetCellValue($COL_BEGRUENDUNG . '3', 'Begründung');
}
if (isset($COL_TAGS)) $objPHPExcel->getActiveSheet()->SetCellValue($COL_TAGS . '3', 'Schlagworte');
$objPHPExcel->getActiveSheet()->SetCellValue($COL_KONTAKT . '3', 'Kontakt');
$objPHPExcel->getActiveSheet()->SetCellValue($COL_VERFAHREN . '3', 'Verfahren');
$objPHPExcel->getActiveSheet()->getStyle($COL_ANTRAGSNR . "3:" . $COL_VERFAHREN . "3")->applyFromArray(array(
	"font" => array(
		"bold" => true
	)
));

$styleThinBlackBorderOutline = array(
	'borders' => array(
		'outline' => array(
			'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
			'color' => array('argb' => 'FF000000'),
		),
	),
);
$objPHPExcel->getActiveSheet()->getStyle($COL_ANTRAGSNR . '2:' . $COL_VERFAHREN . '3')->applyFromArray($styleThinBlackBorderOutline);


PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());


$row = 3;
foreach ($antraege as $ant) {
	/**
	 * @var Antrag $antrag
	 * @var Aenderungsantrag[] $aes
	 */
	$antrag = $ant["antrag"];

	$row++;

	$initiatorInnen_namen   = array();
	$initiatorInnen_kontakt = array();
	foreach ($antrag->antragUnterstuetzerInnen as $unt) {
		if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) {
			$initiatorInnen_namen[] = $unt->getNameMitBeschlussdatum(false);
			if ($unt->person->email != "") $initiatorInnen_kontakt[] = $unt->person->email;
			if ($unt->person->telefon != "") $initiatorInnen_kontakt[] = $unt->person->telefon;
		}
	}

	$objPHPExcel->getActiveSheet()->SetCellValue($COL_ANTRAGSNR . $row, $antrag->revision_name);
	$objPHPExcel->getActiveSheet()->SetCellValue($COL_ANTRAGSTELLERIN . $row, implode(", ", $initiatorInnen_namen));
    $objPHPExcel->getActiveSheet()->SetCellValue($COL_TITEL . $row, $antrag->name);
	$objPHPExcel->getActiveSheet()->SetCellValue($COL_KONTAKT . $row, implode("\n", $initiatorInnen_kontakt));

	$text_antrag   = str_replace(array("[QUOTE]", "[/QUOTE]"), array("\n\n", "\n\n"), $antrag->text);
	$text_antrag   = HtmlBBcodeUtils::removeBBCode($text_antrag);
	$text_antrag   = HtmlBBcodeUtils::text2zeilen(trim($text_antrag), 120, true);
	$zeilen_antrag = array();
	foreach ($text_antrag as $t) {
		$x             = explode("\n", $t);
		$zeilen_antrag = array_merge($zeilen_antrag, $x);
	}

	$text2_antrag   = str_replace(array("[QUOTE]", "[/QUOTE]"), array("\n\n", "\n\n"), $antrag->text2);
	$text2_antrag   = HtmlBBcodeUtils::removeBBCode($text2_antrag);
	$text2_antrag   = HtmlBBcodeUtils::text2zeilen(trim($text2_antrag), 120, true);
	$zeilen2_antrag = array();
	foreach ($text2_antrag as $t) {
		$x             = explode("\n", $t);
		$zeilen2_antrag = array_merge($zeilen2_antrag, $x);
	}

	$text_begruendung   = str_replace(array("[QUOTE]", "[/QUOTE]"), array("\n\n", "\n\n"), $antrag->begruendung);
	$text_begruendung   = HtmlBBcodeUtils::removeBBCode($text_begruendung);
	$text_begruendung   = HtmlBBcodeUtils::text2zeilen(trim($text_begruendung), 120, true);
	$zeilen_begruendung = array();
	foreach ($text_begruendung as $t) {
		$x                  = explode("\n", $t);
		$zeilen_begruendung = array_merge($zeilen_begruendung, $x);
	}

	if ($text_begruendung_zusammen) {
		$text1name = veranstaltungsspezifisch_text1_name($this->veranstaltung, $antrag->typ);
		$text2name = veranstaltungsspezifisch_text2_name($this->veranstaltung, $antrag->typ);
		$begruendungname = veranstaltungsspezifisch_begruendung_name($this->veranstaltung, $antrag->typ);
		$zeilen = array();
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
			if (count($zeilen2_antrag) > $maxlines) $maxlines = count($zeilen2_antrag);
		}

		$objPHPExcel->getActiveSheet()->SetCellValue($COL_ANTRAGSTEXT . $row, trim(implode("\n", $zeilen_antrag)));
		$objPHPExcel->getActiveSheet()->getStyle($COL_ANTRAGSTEXT . $row)->getAlignment()->setWrapText(true);
		if (count($zeilen_antrag) > $maxlines) $maxlines = count($zeilen_antrag);

		$objPHPExcel->getActiveSheet()->SetCellValue($COL_BEGRUENDUNG . $row, trim(implode("\n", $zeilen_begruendung)));
		$objPHPExcel->getActiveSheet()->getStyle($COL_BEGRUENDUNG . $row)->getAlignment()->setWrapText(true);
		if (count($zeilen_begruendung) > $maxlines) $maxlines = count($zeilen_begruendung);

		$objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(14 * $maxlines);
	}

    if (isset($COL_TAGS)) {
        $tags = array();
        foreach ($antrag->tags as $tag) $tags[] = $tag->name;
        $objPHPExcel->getActiveSheet()->SetCellValue($COL_TAGS. $row, implode("\n", $tags));
    }
}

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(3);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_ANTRAGSNR)->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_ANTRAGSTELLERIN)->setWidth(24);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_TITEL)->setWidth(40);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_ANTRAGSTEXT)->setAutoSize(80);
if (isset($COL_ANTRAGSTEXT2)) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($COL_ANTRAGSTEXT2)->setAutoSize(80);
}
if (!$text_begruendung_zusammen) $objPHPExcel->getActiveSheet()->getColumnDimension($COL_BEGRUENDUNG)->setAutoSize(80);
if (isset($COL_TAGS)) $objPHPExcel->getActiveSheet()->getColumnDimension($COL_TAGS)->setAutoSize(18);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_KONTAKT)->setWidth(24);
$objPHPExcel->getActiveSheet()->getColumnDimension($COL_VERFAHREN)->setWidth(13);


$objPHPExcel->getActiveSheet()->setTitle('Anträge');


// Save Excel 2007 file
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
$objWriter->save("php://output");
