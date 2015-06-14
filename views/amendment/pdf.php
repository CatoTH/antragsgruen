<?php

/**
 * @var Amendment $amendment
 */

use app\models\db\Amendment;

$pdfLayout = $amendment->motion->motionType->getPDFLayoutClass();
$pdf       = $pdfLayout->createPDFClass();

header('Content-type: application/pdf; charset=UTF-8');


$initiators = [];
foreach ($amendment->getInitiators() as $init) {
    $initiators[] = $init->getNameWithResolutionDate(false);
}
$initiatorsStr = implode(', ', $initiators);

// set document information
$pdf->SetCreator('Antragsgrün');
$pdf->SetAuthor(implode(", ", $initiators));
$pdf->SetTitle(Yii::t('motion', 'Amendment') . " " . $amendment->getTitle());
$pdf->SetSubject(Yii::t('motion', 'Amendment') . " " . $amendment->getTitle());


// add a page
$pdf->AddPage();

$pdfLayout->printAmendmentHeader($amendment);

foreach ($amendment->getSortedSections(true) as $section) {
    $section->getSectionType()->printAmendmentToPDF($pdf);
}

$pdf->Output('Amendment_' . $amendment->titlePrefix . '.pdf', 'I');

die();
