<?php
/**
 * @var Aenderungsantrag $model
 * @var Sprache $sprache
 * @var bool $diff_ansicht
 */

// Muss am Anfang stehen, ansonsten zerhaut's die Zeilenumbrüche; irgendwas mit dem internen Encoding
$absae = $model->getAntragstextParagraphs();

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$initiatorinnen       = array();
$initiatorinnen_namen = array();
$unterstuetzer        = array();
foreach ($model->aenderungsantragUnterstuetzer as $unt) {
	if ($unt->rolle == IUnterstuetzer::$ROLLE_INITIATOR) {
		$initiatorinnen[]       = $unt->unterstuetzer;
		$initiatorinnen_namen[] = $unt->unterstuetzer->name;
	}
	if ($unt->rolle == IUnterstuetzer::$ROLLE_UNTERSTUETZER) $unterstuetzer[] = $unt->unterstuetzer;
}

// set document information
$pdf->SetCreator(PDF_CREATOR);
if (count($initiatorinnen_namen) > 0) $pdf->SetAuthor(implode(", ", $initiatorinnen_namen));
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

// set font
$pdf->SetFont('dejavusans', '', 10);

// add a page
$pdf->AddPage();


if ($model->antrag->veranstaltung0->yii_url == "ltwby13-programm") {
	$logo = Yii::app()->basePath . "/../html/images/gruene-bayern-sw.jpg";
} else {
	$logo = Yii::app()->params['pdf_logo'];
}

if (file_exists($logo)) {
	$pdf->setJPEGQuality(100);
	$pdf->Image($logo, 22, 32, 47, 26);
}

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

$str = "Änderungsantrag";
$pdf->SetFont("helvetica", "B", "25");
$width = $pdf->GetStringWidth($str);

$pdf->SetXY((210 - $width) / 2, 60);
$pdf->Write(20, $str);
$pdf->SetLineStyle(array(
	"width" => 3,
	'color' => array(150, 150, 150),
));
$pdf->Line((210 - $width) / 2, 78, (210 + $width) / 2, 78);

$pdf->SetY(90);

if ($model->antrag->veranstaltung0->antrag_einleitung != "") {
	$pdf->SetX(25);
	$pdf->SetFont("helvetica", "B", 12);
	$pdf->MultiCell(160, 13, $model->antrag->veranstaltung0->antrag_einleitung);
}

$pdf->SetX(25);

$pdf->SetFont("helvetica", "B", 12);
$pdf->MultiCell(50, 0, "AntragsstellerIn:", 0, "L", false, 0);
$pdf->SetFont("helvetica", "", 12);
$pdf->MultiCell(120, 0, implode(", ", $initiatorinnen_namen), 0, "L");

$pdf->SetFont("helvetica", "B", 8);
$pdf->Ln();

$pdf->SetFont("helvetica", "B", 12);
$pdf->MultiCell(50, 0, "Gegenstand:", 0, "L", false, 0);
$pdf->SetFont("helvetica", "B", 12);
$pdf->MultiCell(100, 0, $model->antrag->name,
	array('B' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(150, 150, 150))),
	"L"
);

/*
$pdf->Ln();
$pdf->SetFont("helvetica", "", 12);
$pdf->writeHTML("<h3>Antrag</h3>");
*/
$pdf->Ln(8);
$pdf->SetFont("Courier", "", 10);


if ($diff_ansicht) {

	$abs_alt = $model->antrag->getParagraphs();
	$abs_neu = json_decode($model->text_neu);

	$html = "";

	$letztes_leer = true;
	foreach ($abs_alt as $i=> $abs) {
		if (isset($abs_neu[$i]) && $abs_neu[$i] != "") {
			if ($letztes_leer) {
				$letztes_leer = false;

				preg_match_all("/<span class='zeilennummer'>([0-9]+)<\/span>/siu", $abs->str_html, $matches);
				$zeile_von = (isset($matches[1][0]) ? IntVal($matches[1][0]) : "????");
				$zeile_bis = (isset($matches[1]) ? $matches[1][count($matches[1])-1] : "???");

				$html .= "Im Absatz von Zeile $zeile_von - $zeile_bis:";
			}
			$html .= "<div class='row-fluid' style=\"line-height: 6px;\">";
			/** @var AntragAbsatz $abs */
			$str = DiffUtils::renderBBCodeDiff2HTML($abs->str_bbcode, $abs_neu[$i], true);

			$str = str_replace(
				array("<ins>", "</ins>", "<del>", "</del>"),
				array("<span style=\"color: green; text-decoration: underline;\">", "</span>", "<span style=\"color: red; text-decoration: line-through;\">", "</span>"),
				$str
			);
			$html .= $str;
			$html .= "</div>\n";
		}
	}

	$pdf->writeHTML($html);

} else {

	$linenr = 1;

	foreach ($absae as $i => $abs) {
		/** @var AntragAbsatz $abs */
		$text   = $abs->str_html;
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
		$pdf->writeHTMLCell(170, '', 24, '', $text, 0, 1, 0, true, '', true);

		$pdf->Ln(4);

	}

}
$html = '
	</div>
	<h3 style="margin-top: 0;">Begründung</h3>
	<div class="textholder consolidated">
		' . HtmlBBcodeUtils::bbcode2html($model->aenderung_begruendung) . '
	</div>
</div>';


$pdf->SetFont("helvetica", "", 10);
$pdf->writeHTML($html, true, false, true, false, '');


//Close and output PDF document
$pdf->Output('Antrag_' . $model->revision_name . '.pdf', 'I');
