<?php

/**
 * @var VeranstaltungController $this
 * @var Veranstaltung $veranstaltung
 * @var array|Aenderungsantrag[] $aenderungsantraege
 * @var Sprache $sprache
 */


header('Content-type: application/pdf; charset=UTF-8');


$cached = Yii::app()->cache->get("pdf_ae_" . $veranstaltung->id);
if ($cached !== false) {
	echo $cached;
} else {


// create new PDF document
	$pdf = new AntragsgruenPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetTitle($veranstaltung->name);
//$pdf->SetSubject($sprache->get("Antrag") . " " . $model->revision_name . ": " . $model->name);
//$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
//$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 006', PDF_HEADER_STRING);

// set header and footer fonts
//$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
//$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
	$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(true);

//set margins
	$pdf->SetMargins(25, 40, 25);
//$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
//$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM - 5);

//set image scale factor
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);


	$first = true;
	foreach ($aenderungsantraege as $ae) {
		$initiatorInnen = array();
		$unterstuetzerInnen  = array();
		foreach ($ae->aenderungsantragUnterstuetzerInnen as $unt) {
			if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) $initiatorInnen[] = $unt->person->name;
			if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN) $unterstuetzerInnen[] = $unt->person;
		}

		$this->widget("AenderungsantragPDFWidget", array(
			"sprache"          => $sprache,
			"aenderungsantrag" => $ae,
			"pdf"              => $pdf,
			"initiatorinnen"   => implode(", ", $initiatorInnen),
		));
		$first = false;
	}


	$pdftext = $pdf->Output('Aenderungsantraege.pdf', 'S');


	Yii::app()->cache->set("pdf_ae_" . $veranstaltung->id, $pdftext);
	echo $pdftext;
}