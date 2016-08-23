<?php

/**
 * @var Motion $motion
 */

use app\models\db\Motion;

$pdfLayout = $motion->motionType->getPDFLayoutClass();
$pdf       = $pdfLayout->createPDFClass();


$initiators = [];
foreach ($motion->getInitiators() as $init) {
    $initiators[] = $init->getNameWithResolutionDate(false);
}
$initiatorsStr = implode(', ', $initiators);

// set document information
$pdf->SetCreator('Antragsgrün');
$pdf->SetAuthor(implode(', ', $initiators));
$pdf->SetTitle(Yii::t('motion', 'Motion') . " " . $motion->getTitleWithPrefix());
$pdf->SetSubject(Yii::t('motion', 'Motion') . " " . $motion->getTitleWithPrefix());


// add a page
$pdf->AddPage();

$pdfLayout->printMotionHeader($motion);


foreach ($motion->getSortedSections(true) as $section) {
	// echo '<pre>'.print_r($section->getSectionType(),true).'</pre><hr/>';
    $section->getSectionType()->printMotionToPDF($pdfLayout, $pdf);
}


$pdf->Output($motion->getFilenameBase(true) . '.pdf', 'I');

die();
