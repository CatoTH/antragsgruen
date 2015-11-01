<?php

use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion[] $motions
 */

if (count($motions) == 0) {
    $pdf->AddPage();
    $pdf->Output('Motions.pdf', 'I');

    die();
}

$pdfLayout = $motions[0]->motionType->getPDFLayoutClass();
$pdf       = $pdfLayout->createPDFClass();

// set document information
$pdf->SetCreator('Antragsgrün');
$pdf->SetAuthor('Antragsgrün');
$pdf->SetTitle(Yii::t('export', 'all_motions_title'));
$pdf->SetSubject(Yii::t('export', 'all_motions_title'));


try {
    foreach ($motions as $motion) {
        // add a page
        $pdf->AddPage();

        $pdfLayout->printMotionHeader($motion);

        foreach ($motion->getSortedSections(true) as $section) {
            $section->getSectionType()->printMotionToPDF($pdfLayout, $pdf);
        }
    }

} catch (\Exception $e) {
    echo 'Ein Fehler trat auf: ' . Html::encode($e);
    die();
}

$pdf->Output('Motions.pdf', 'I');

die();
