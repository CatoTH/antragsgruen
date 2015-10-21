<?php

use app\components\latex\Exporter;
use app\components\latex\Layout;
use app\models\db\Amendment;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

/**
 * @var Amendment[] $amendments
 */

if (count($amendments) == 0) {
    $pdf->AddPage();
    $pdf->Output('Amendments.pdf', 'I');

    die();
}

$pdfLayout = $amendments[0]->motion->motionType->getPDFLayoutClass();
$pdf       = $pdfLayout->createPDFClass();

// set document information
$pdf->SetCreator('Antragsgrün');
$pdf->SetAuthor('Antragsgrün');
$pdf->SetTitle(Yii::t('pdf', 'all_amendments_title'));
$pdf->SetSubject(Yii::t('pdf', 'all_amendments_title'));

try {
    foreach ($amendments as $amendment) {
        // add a page
        $pdf->AddPage();

        $pdfLayout->printAmendmentHeader($amendment);

        // @TODO: Editorial change

        foreach ($amendment->getSortedSections(false) as $section) {
            $section->getSectionType()->printAmendmentToPDF($pdfLayout, $pdf);
        }

        // @TODO: Editorial Explanation
    }


} catch (\Exception $e) {
    echo 'Ein Fehler trat auf: ' . Html::encode($e);
    die();
}

$pdf->Output('Motions.pdf', 'I');

die();
