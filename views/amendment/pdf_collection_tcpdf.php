<?php

use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var Amendment[] $amendments
 */

if (count($amendments) === 0) {
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->Output('Amendments.pdf', 'I');

    die();
}

$pdfLayout = $amendments[0]->getMyMotion()->getMyMotionType()->getPDFLayoutClass();
$pdf       = $pdfLayout->createPDFClass();

// set document information
$pdf->SetCreator(Yii::t('export', 'default_creator'));
$pdf->SetAuthor(Yii::t('export', 'default_creator'));
$pdf->SetTitle(Yii::t('export', 'all_amendments_title'));
$pdf->SetSubject(Yii::t('export', 'all_amendments_title'));

try {
    foreach ($amendments as $amendment) {
        \app\views\amendment\LayoutHelper::printToPDF($pdf, $pdfLayout, $amendment);
    }
} catch (Exception $e) {
    echo Yii::t('base', 'err_unknown') . ': ' . Html::encode($e);
    die();
}

$pdf->Output('Amendments.pdf', 'I');
