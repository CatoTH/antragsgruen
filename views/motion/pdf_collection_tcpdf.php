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
        \app\views\motion\LayoutHelper::printToPDF($pdf, $pdfLayout, $motion);
    }

} catch (\Exception $e) {
    echo 'Error: ' . Html::encode($e);
    die();
}

$pdf->Output('Motions.pdf', 'I');

die();
