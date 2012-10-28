<?php
/* @var $model Antrag */

// Muss am Anfang stehen, ansonsten zerhaut's die Zeilenumbrüche; irgendwas mit dem internen Encoding
$absae = $model->getParagraphs();


// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$initiator = null;
$unterstuetzer = array();
foreach ($model->antragUnterstuetzer as $unt) {
	if ($unt->rolle == IUnterstuetzer::$ROLLE_INITIATOR) $initiator = $unt->unterstuetzer;
	if ($unt->rolle == IUnterstuetzer::$ROLLE_UNTERSTUETZER) $unterstuetzer[] = $unt->unterstuetzer;
}

// set document information
$pdf->SetCreator(PDF_CREATOR);
if ($initiator) $pdf->SetAuthor($initiator->name);
$pdf->SetTitle("Antrag " . $model->revision_name . ": " . $model->name);
$pdf->SetSubject("Antrag " . $model->revision_name . ": " . $model->name);
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
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ---------------------------------------------------------

// set font
$pdf->SetFont('dejavusans', '', 10);

// add a page
$pdf->AddPage();

$pdf->setJPEGQuality(100);
$pdf->Image("/var/www/parteitool/html/images/MCS_Gruene_Logo_weiss_RZ.jpg", 22, 32, 47, 26);

$pdf->SetXY(155, 37, true);

if ($model->revision_name == "") {
	$name = "Entwurf";
	$pdf->SetFont("helvetica", "I", "25");
} else {
	$name = $model->revision_name;
	$pdf->SetFont("helvetica", "B", "25");
}
$pdf->MultiCell(37, 21, $name,
	array('LTRB' => array('width' => 3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(150, 150, 150))), "C",
	false, 1, "", "", true, 0, false, true, 21, // defaults
	"M"
);

$str = Antrag::$TYPEN[$model->typ];
$pdf->SetFont("helvetica", "B", "25");
$width = $pdf->GetStringWidth($str);

$pdf->SetXY((210 - $width) / 2, 60);
$pdf->Write(20, $str);
$pdf->SetLineStyle(array(
	"width" => 3,
	'color' => array(150, 150, 150),
));
$pdf->Line((210 - $width) / 2, 78, (210 + $width) / 2, 78);

$pdf->SetXY(25, 90);
$pdf->SetFont("helvetica", "B", 12);
$pdf->MultiCell(160, 13, $model->veranstaltung0->antrag_einleitung);

$pdf->SetXY(25, 110);

$pdf->SetFont("helvetica", "B", 12);
$pdf->MultiCell(50, 0, "AntragsstellerIn:", 0, "L", false, 0);
$pdf->SetFont("helvetica", "", 12);
$pdf->MultiCell(150, 0, $initiator->name, 0, "L");

$pdf->SetFont("helvetica", "B", 8);
$pdf->Ln();

$pdf->SetFont("helvetica", "B", 12);
$pdf->MultiCell(50, 0, "Gegenstand:", 0, "L", false, 0);
$pdf->SetFont("helvetica", "B", 12);
$pdf->MultiCell(100, 0, $model->name,
	array('B' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(150, 150, 150))),
	"L"
);

$pdf->Ln();

$pdf->SetFont("helvetica", "", 12);

$pdf->writeHTML("<h3>Antrag</h3>");
$pdf->SetFont("Courier", "", 10);
$pdf->Ln(8);


$linenr = 1;

foreach ($absae as $i=>$abs) {
	/** @var AntragAbsatz $abs */
	$text = $abs->str_html;
	$zeilen = substr_count($text, "<span class='zeilennummer'>");

	$abstand_bevor = array();

	//preg_match_all("/<div[^>]*antragabsatz_holder[^>]*>(?:.*)<span class=[\"']zeilennummer[\"']>([0-9]+)<\/span>/siuU", $text, $matches);
	//foreach ($matches[1] as $line) if ($line > 1) $abstand_bevor[$line] = 25;

	preg_match_all("/<li><span class=[\"']zeilennummer[\"']>([0-9]+)<\/span>/siuU", $text, $matches);
	foreach ($matches[1] as $line) if (isset($abstand_bevor[$line])) $abstand_bevor[$line] += 10;
	else $abstand_bevor[$line] = 10;

	preg_replace("/<li><span class=[\"']zeilennummer[\"']>([0-9]+)<\/span>/siuU", "<li style='margin-top: 10px;'>", $text);

	preg_match_all("/<div[^>]*antragabsatz_holder[^>]*>(?:.*)<span class=[\"']zeilennummer[\"']>([0-9]+)<\/span>/siuU", $text, $matches);

	$text = preg_replace("/<span class=[\"']zeilennummer[\"']>([0-9]+)<\/span>/sii", "", $text);

	$zeilennrs = array();
	for ($i = 0; $i < $zeilen; $i++) $zeilennrs[] = $linenr++;
	$text2 = implode("<br>", $zeilennrs);

	$y = $pdf->getY();
	$pdf->writeHTMLCell(10, '', 12, $y, $text2, 0, 0, 0, true, '', true);
	$pdf->writeHTMLCell(170, '',24, '', $text, 0, 1, 0, true, '', true);

	$pdf->Ln(8);

}

$html = '
	</div>
	<h3>Begründung</h3>
	<div class="textholder consolidated">
		' . HtmlBBcodeUtils::bbcode2html($model->begruendung) . '
	</div>
</div>';


$pdf->SetFont("helvetica", "", 10);
$pdf->writeHTML($html, true, false, true, false, '');


//Close and output PDF document
$pdf->Output('Antrag_' . $model->revision_name . '.pdf', 'I');
