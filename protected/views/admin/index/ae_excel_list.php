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
header('Content-Disposition: attachment;filename=aenderungsantraege.xlsx');
header('Cache-Control: max-age=0');


define('PCLZIP_TEMPORARY_DIR', '/tmp/');
PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);

$objPHPExcel = new PHPExcel();

$objPHPExcel->getProperties()->setCreator("Antragsgruen.de");
$objPHPExcel->getProperties()->setLastModifiedBy("Antragsgruen.de");
$objPHPExcel->getProperties()->setTitle($this->veranstaltung->name);
$objPHPExcel->getProperties()->setSubject("Änderungsanträge");
$objPHPExcel->getProperties()->setDescription($this->veranstaltung->name . " - Änderungsanträge");


$objPHPExcel->setActiveSheetIndex(0);

$objPHPExcel->getActiveSheet()->SetCellValue('B2', "Antragsübersicht");
$objPHPExcel->getActiveSheet()->getStyle("B2")->applyFromArray(array(
	"font" => array(
		"bold" => true
	)
));

$objPHPExcel->getActiveSheet()->SetCellValue('B3', 'Antragsnr.');
$objPHPExcel->getActiveSheet()->SetCellValue('C3', 'Antragsteller');
$objPHPExcel->getActiveSheet()->SetCellValue('D3', 'Zeile');
$objPHPExcel->getActiveSheet()->SetCellValue('E3', 'Titel/Änderung');
$objPHPExcel->getActiveSheet()->SetCellValue('F3', 'Begründung');
$objPHPExcel->getActiveSheet()->SetCellValue('G3', 'Verfahren');
$objPHPExcel->getActiveSheet()->getStyle("B3:G3")->applyFromArray(array(
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
$objPHPExcel->getActiveSheet()->getStyle('B2:G3')->applyFromArray($styleThinBlackBorderOutline);


PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());


$row = 3;
foreach ($antraege as $ant) {
	/**
	 * @var Antrag $antrag
	 * @var Aenderungsantrag[] $aes
	 */
	$antrag = $ant["antrag"];
	$aes = $ant["aes"];
	$row++;
	$row++;

	$antrag_row_from = $row;

	$initiatorInnen_namen     = array();
	foreach ($antrag->antragUnterstuetzerInnen as $unt) {
		if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) {
			$initiatorInnen_namen[] = $unt->person->name;
		}
	}

	$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $antrag->revision_name);
	$objPHPExcel->getActiveSheet()->SetCellValue('C' . $row, implode(", ", $initiatorInnen_namen));

	foreach ($aes as $ae) {
		$row++;

		$initiatorInnen_namen     = array();
		foreach ($ae->aenderungsantragUnterstuetzerInnen as $unt) {
			if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) {
				$initiatorInnen_namen[] = $unt->person->name;
			}
		}

		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $ae->revision_name);
		$objPHPExcel->getActiveSheet()->SetCellValue('C' . $row, implode(", ", $initiatorInnen_namen));
		$objPHPExcel->getActiveSheet()->SetCellValue('D' . $row, $ae->getFirstDiffLine());

		$text = str_replace(array("[QUOTE]", "[/QUOTE]"), array("\n\n", "\n\n"), $ae->aenderung_text);
		$text   = HtmlBBcodeUtils::text2zeilen(trim($text), 120);
		$zeilen = array();
		foreach ($text as $t) {
			$x      = explode("\n", $t);
			$zeilen = array_merge($zeilen, $x);
		}
		$objPHPExcel->getActiveSheet()->SetCellValue('E' . $row, trim(implode("\n", $zeilen)));
		$objPHPExcel->getActiveSheet()->getStyle('E' . $row)->getAlignment()->setWrapText(true);

		$objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(14 * count($zeilen));

		$text = str_replace(array("[QUOTE]", "[/QUOTE]"), array("\n\n", "\n\n"), $ae->aenderung_begruendung);
		$text   = HtmlBBcodeUtils::text2zeilen(trim($text), 120);
		$zeilen = array();
		foreach ($text as $t) {
			$x      = explode("\n", $t);
			$zeilen = array_merge($zeilen, $x);
		}
		$objPHPExcel->getActiveSheet()->SetCellValue('F' . $row, trim(implode("\n", $zeilen)));
		$objPHPExcel->getActiveSheet()->getStyle('F' . $row)->getAlignment()->setWrapText(true);

	}

	$objPHPExcel->getActiveSheet()->getStyle('B' . $antrag_row_from . ':G' . $row)->applyFromArray($styleThinBlackBorderOutline);

}

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(3);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(24);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(13);


$objPHPExcel->getActiveSheet()->setTitle('Änderungsanträge');


// Save Excel 2007 file
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
$objWriter->save("php://output");
