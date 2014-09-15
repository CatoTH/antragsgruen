<?php
/**
 * @var IndexController $this
 * @var array $antraege
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

$objPHPExcel = new PHPExcel();

$objPHPExcel->getProperties()->setCreator("Antragsgruen.de");
$objPHPExcel->getProperties()->setLastModifiedBy("Antragsgruen.de");
$objPHPExcel->getProperties()->setTitle($this->veranstaltung->name);
$objPHPExcel->getProperties()->setSubject("Anträge");
$objPHPExcel->getProperties()->setDescription($this->veranstaltung->name . " - Anträge");


$objPHPExcel->setActiveSheetIndex(0);

$objPHPExcel->getActiveSheet()->SetCellValue('B2', "Antragsübersicht");
$objPHPExcel->getActiveSheet()->getStyle("B2")->applyFromArray(array(
	"font" => array(
		"bold" => true
	)
));

$objPHPExcel->getActiveSheet()->SetCellValue('B3', 'Antragsnr.');
$objPHPExcel->getActiveSheet()->SetCellValue('C3', 'Antragsteller');
$objPHPExcel->getActiveSheet()->SetCellValue('D3', 'Antragstext');
$objPHPExcel->getActiveSheet()->SetCellValue('E3', 'Begründung');
$objPHPExcel->getActiveSheet()->SetCellValue('F3', 'Verfahren');
$objPHPExcel->getActiveSheet()->getStyle("B3:F3")->applyFromArray(array(
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
$objPHPExcel->getActiveSheet()->getStyle('B2:F3')->applyFromArray($styleThinBlackBorderOutline);


PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());


$row = 3;
foreach ($antraege as $ant) {
	/**
	 * @var Antrag $antrag
	 * @var Aenderungsantrag[] $aes
	 */
	$antrag = $ant["antrag"];

	$row++;

	$initiatorInnen_namen = array();
	foreach ($antrag->antragUnterstuetzerInnen as $unt) {
		if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) {
			$initiatorInnen_namen[] = $unt->person->name;
		}
	}

	$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $antrag->revision_name);
	$objPHPExcel->getActiveSheet()->SetCellValue('C' . $row, implode(", ", $initiatorInnen_namen));

	$text   = str_replace(array("[QUOTE]", "[/QUOTE]"), array("\n\n", "\n\n"), $antrag->text);
	$text   = HtmlBBcodeUtils::text2zeilen(trim($text), 120);
	$zeilen = array();
	foreach ($text as $t) {
		$x      = explode("\n", $t);
		$zeilen = array_merge($zeilen, $x);
	}
	$objPHPExcel->getActiveSheet()->SetCellValue('D' . $row, "\"" . str_replace("\"", "\"\"", trim(implode("\n", $zeilen))) . "\"");
	$objPHPExcel->getActiveSheet()->getStyle('D' . $row)->getAlignment()->setWrapText(true);

	$objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(14 * count($zeilen));

	$text   = str_replace(array("[QUOTE]", "[/QUOTE]"), array("\n\n", "\n\n"), $antrag->begruendung);
	$text   = HtmlBBcodeUtils::text2zeilen(trim($text), 120);
	$zeilen = array();
	foreach ($text as $t) {
		$x      = explode("\n", $t);
		$zeilen = array_merge($zeilen, $x);
	}
	$objPHPExcel->getActiveSheet()->SetCellValue('E' . $row, "\"" . str_replace("\"", "\"\"", trim(implode("\n", $zeilen))) . "\"");
	$objPHPExcel->getActiveSheet()->getStyle('E' . $row)->getAlignment()->setWrapText(true);

}

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(3);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(24);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(13);


$objPHPExcel->getActiveSheet()->setTitle('Anträge');


// Save Excel 2007 file
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
$objWriter->save("php://output");
