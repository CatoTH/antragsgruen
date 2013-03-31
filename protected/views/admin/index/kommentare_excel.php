<?php
/**
 * @var IndexController $this
 * @var array|IKommentar[] $kommentare
 */

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename=kommentare.xlsx');
header('Cache-Control: max-age=0');


define('PCLZIP_TEMPORARY_DIR', '/tmp/');
PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);

$objPHPExcel = new PHPExcel();

$objPHPExcel->getProperties()->setCreator("Antragsgruen.de");
$objPHPExcel->getProperties()->setLastModifiedBy("Antragsgruen.de");
$objPHPExcel->getProperties()->setTitle($this->veranstaltung->name);
$objPHPExcel->getProperties()->setSubject("Kommentare");
$objPHPExcel->getProperties()->setDescription($this->veranstaltung->name . " - Kommentare zu Anträgen und Änderungsanträgen");


$objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Datum');
$objPHPExcel->getActiveSheet()->SetCellValue('B1', 'Person');
$objPHPExcel->getActiveSheet()->SetCellValue('C1', 'Antrag');
$objPHPExcel->getActiveSheet()->SetCellValue('D1', 'Absatz / Zeilen');
$objPHPExcel->getActiveSheet()->SetCellValue('E1', 'Kommentar');
if ($this->veranstaltung->getEinstellungen()->kommentare_unterstuetzbar) {
	$objPHPExcel->getActiveSheet()->SetCellValue('F1', 'Bewertung: Positiv');
	$objPHPExcel->getActiveSheet()->SetCellValue('G1', 'Bewertung: Negativ');

	$objPHPExcel->getActiveSheet()->getStyle("A1:G1")->applyFromArray(array(
		"font" => array(
			"bold" => true
		)
	));
} else {
	$objPHPExcel->getActiveSheet()->getStyle("A1:E1")->applyFromArray(array(
		"font" => array(
			"bold" => true
		)
	));
}

PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

$row = 1;
foreach ($kommentare as $kommentar) {
	$row++;

	$timestamp = strtotime($kommentar->datum);
	$objPHPExcel->getActiveSheet()->SetCellValue('A' . $row, PHPExcel_Shared_Date::PHPToExcel($timestamp));
	$objPHPExcel->getActiveSheet()->getStyle('A' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);

	$verfasserIn = $kommentar->verfasserIn->name;
	if ($kommentar->verfasserIn->email != "") $verfasserIn .= " (" . $kommentar->verfasserIn->email . ")";
	$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $verfasserIn);

	$objPHPExcel->getActiveSheet()->getStyle('C' . $row)->getAlignment()->setWrapText(true);
	if (is_a($kommentar, "AntragKommentar")) {
		/** @var AntragKommentar $kommentar */
		$antrag = Antrag::model()->findByPk($kommentar->antrag_id);
		if ($this->veranstaltung->getEinstellungen()->revision_name_verstecken) $objPHPExcel->getActiveSheet()->SetCellValue('C' . $row, $kommentar->antrag->name);
		else $objPHPExcel->getActiveSheet()->SetCellValue('C' . $row, $kommentar->antrag->revision_name . " " . $kommentar->antrag->name);

		/** @var Antrag $antrag */
		$absaetze = $antrag->getParagraphs();
		if (isset($absaetze[$kommentar->absatz])) {
			$html = $absaetze[$kommentar->absatz]->str_html;
			preg_match_all("/<span class='zeilennummer'>([0-9]+)<\/span>/siu", $html, $matches);
			$zeile_von = (isset($matches[1][0]) ? IntVal($matches[1][0]) : "????");
			$zeile_bis = (isset($matches[1]) ? $matches[1][count($matches[1]) - 1] : "???");

			$absatz_zeilen = ($kommentar->absatz + 1) . " / Z. $zeile_von - $zeile_bis";
		} else {
			$absatz_zeilen = "???";
		}
	}
	if (is_a($kommentar, "AenderungsantragKommentar")) {
		/** @var AenderungsantragKommentar $kommentar */
		if ($this->veranstaltung->getEinstellungen()->revision_name_verstecken) $objPHPExcel->getActiveSheet()->SetCellValue('C' . $row, $kommentar->aenderungsantrag->revision_name);
		else $objPHPExcel->getActiveSheet()->SetCellValue('C' . $row, $kommentar->aenderungsantrag->revision_name . " zu " . $kommentar->aenderungsantrag->antrag->revision_name);

		$absatz_zeilen = "";
	}

	$objPHPExcel->getActiveSheet()->SetCellValue('D' . $row, $absatz_zeilen);
	$objPHPExcel->getActiveSheet()->getStyle('D' . $row)->getAlignment()->setWrapText(true);

	$text   = HtmlBBcodeUtils::text2zeilen($kommentar->text, 120);
	$zeilen = array();
	foreach ($text as $t) {
		$x      = explode("\n", $t);
		$zeilen = array_merge($zeilen, $x);
	}
	$objPHPExcel->getActiveSheet()->SetCellValue('E' . $row, trim(implode("\n", $zeilen)));
	$objPHPExcel->getActiveSheet()->getStyle('E' . $row)->getAlignment()->setWrapText(true);

	if ($this->veranstaltung->getEinstellungen()->kommentare_unterstuetzbar) {
		$positiv = $negativ = 0;
		if (is_a($kommentar, "AntragKommentar")) {
			/** @var AntragKommentar $kommentar */
			foreach ($kommentar->unterstuetzerInnen as $unt) {
				if ($unt->dafuer) $positiv++;
				else $negativ++;
			}
		}
		$objPHPExcel->getActiveSheet()->SetCellValue('F' . $row, $positiv);
		$objPHPExcel->getActiveSheet()->SetCellValue('G' . $row, $negativ);
	}

	$objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(14 * count($zeilen));
}

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);


$objPHPExcel->getActiveSheet()->setTitle('Kommentare');


// Save Excel 2007 file
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
$objWriter->save("php://output");
