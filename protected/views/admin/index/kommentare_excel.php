<?php
/**
 * @var IndexController $this
 * @var array|IKommentar[] $kommentare
 */

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename=kommentare.xlsx');
header('Cache-Control: max-age=0');


define( 'PCLZIP_TEMPORARY_DIR', '/tmp/' );
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
$objPHPExcel->getActiveSheet()->SetCellValue('D1', 'Kommentar');
$objPHPExcel->getActiveSheet()->getStyle("A1:D1")->applyFromArray(array(
	"font" => array(
		"bold" => true
	)
));

PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );

$row = 1;
foreach ($kommentare as $kommentar) {
	$row++;
	$objPHPExcel->getActiveSheet()->SetCellValue('A' . $row, $kommentar->datum);
	$objPHPExcel->getActiveSheet()->getStyle('A' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDDSLASH);
	$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $kommentar->verfasser->name);

	$objPHPExcel->getActiveSheet()->getStyle('C' . $row)->getAlignment()->setWrapText(true);
	if (is_a($kommentar, "AntragKommentar")) {
		/** @var AntragKommentar $kommentar */
		$antrag = Antrag::model()->findByPk($kommentar->antrag_id);
		if ($this->veranstaltung->revision_name_verstecken) $objPHPExcel->getActiveSheet()->SetCellValue('C' . $row, $kommentar->antrag->name);
		else $objPHPExcel->getActiveSheet()->SetCellValue('C' . $row, $kommentar->antrag->revision_name);
	}
	if (is_a($kommentar, "AenderungsantragKommentar")) {
		/** @var AenderungsantragKommentar $kommentar */
		if ($this->veranstaltung->revision_name_verstecken) $objPHPExcel->getActiveSheet()->SetCellValue('C' . $row, $kommentar->aenderungsantrag->revision_name);
		else $objPHPExcel->getActiveSheet()->SetCellValue('C' . $row, $kommentar->aenderungsantrag->revision_name . " zu " . $kommentar->aenderungsantrag->antrag->revision_name);
	}

	$text = HtmlBBcodeUtils::text2zeilen($kommentar->text, 120);
	$zeilen = array();
	foreach ($text as $t) {
		$x = explode("\n", $t);
		$zeilen = array_merge($zeilen, $x);
	}

	$objPHPExcel->getActiveSheet()->SetCellValue('D' . $row, trim(implode("\n", $zeilen)));
	$objPHPExcel->getActiveSheet()->getStyle('D' . $row)->getAlignment()->setWrapText(true);
	$objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(14 * count($zeilen));
}

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);



$objPHPExcel->getActiveSheet()->setTitle('Kommentare');


// Save Excel 2007 file
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
$objWriter->save("php://output");
