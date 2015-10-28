<?php

use app\models\db\Amendment;
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
        \app\views\amendment\LayoutHelper::printToPDF($pdf, $pdfLayout, $amendment);
    }
} catch (\Exception $e) {
    echo \Yii::t('base', 'err_unknown') . ': ' . Html::encode($e);
    die();
}

$pdf->Output('Motions.pdf', 'I');

die();
