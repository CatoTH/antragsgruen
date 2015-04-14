<?php

/**
 * @var Motion $motion
 */

use app\models\db\Motion;

$pdfLayout = $motion->consultation->getPDFLayoutClass();
$pdf       = $pdfLayout->createPDFClass();
$wording   = $motion->consultation->getWording();

header('Content-type: application/pdf; charset=UTF-8');


$initiatorinnen = ['Ich'];

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(implode(", ", $initiatorinnen));
$pdf->SetTitle($wording->get("Antrag") . " " . $motion->getTitleWithPrefix());
$pdf->SetSubject($wording->get("Antrag") . " " . $motion->getTitleWithPrefix());

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);

$pdf->SetMargins(25, 40, 25);
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM - 5);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->SetFont('dejavusans', '', 10);

$pdf->AddPage();
$pdf->writeHTML('Test');

$pdf->Output('Antrag_' . $motion->titlePrefix . '.pdf', 'I');



die();