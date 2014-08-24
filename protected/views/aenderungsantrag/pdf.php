<?php
/**
 * @var Aenderungsantrag $model
 * @var Sprache $sprache
 * @var bool $diff_ansicht
 * @var bool $long_name
 */

// Muss am Anfang stehen, ansonsten zerhaut's die Zeilenumbrüche; irgendwas mit dem internen Encoding
$absae = $model->getAntragstextParagraphs_flat();

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$initiatorInnen           = array();
$initiatorInnen_namen     = array();
$unterstuetzerInnen       = array();
$unterstuetzerInnen_namen = array();
foreach ($model->aenderungsantragUnterstuetzerInnen as $unt) {
	if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) {
		$initiatorInnen[]       = $unt->person;
		$initiatorInnen_namen[] = $unt->person->name;
	}
	if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN) {
		$unterstuetzerInnen[]       = $unt->person;
		$unterstuetzerInnen_namen[] = $unt->person->name;
	}
}

// set document information
$pdf->SetCreator(PDF_CREATOR);
if (count($initiatorInnen_namen) > 0) $pdf->SetAuthor(implode(", ", $initiatorInnen_namen));
$pdf->SetTitle("Änderungsantrag " . $model->revision_name . " zu " . $model->antrag->name);
$pdf->SetSubject("Änderungsantrag " . $model->revision_name . " zu " . $model->antrag->name);
//$pdf->SetSubject($model->name);
//$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
//$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 006', PDF_HEADER_STRING);

// set header and footer fonts
//$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
//$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

//set margins
$pdf->SetMargins(25, 40, 25);
//$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
//$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM - 5);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ---------------------------------------------------------

$this->widget("AenderungsantragPDFWidget", array(
	"sprache" => $sprache,
	"aenderungsantrag" => $model,
	"pdf" => $pdf,
	"initiatorinnen" => implode(", ", $initiatorInnen),
	"diff_ansicht" => $diff_ansicht
));



if ($long_name && preg_match("/^Ä[0-9]+$/", $model->revision_name)) {
	$nr = str_replace("Ä", "", $model->revision_name);
	if ($nr < 10) $nr = "00" . $nr;
	elseif ($nr < 100) $nr = "0" . $nr;
	$pdf->Output('Aenderungsantrag_' . $nr . '.pdf', 'I');
} else {
	$pdf->Output('Antrag_' . $model->revision_name . '.pdf', 'I');
}
