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

\app\views\motion\LayoutHelper::printToPDF($pdf, $pdfLayout, $motion);

$pdf->Output($motion->getFilenameBase(true) . '.pdf', 'I');

die();
