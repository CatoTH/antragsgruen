<?php

use app\models\db\Amendment;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var Amendment[] $amendments
 */

$pdfLayout = $motion->motionType->getPDFLayoutClass();
$pdf       = $pdfLayout->createPDFClass();

// set document information
$pdf->SetCreator('Antragsgrün');
$pdf->SetAuthor('Antragsgrün');
$pdf->SetTitle(Yii::t('export', 'all_amendments_title'));
$pdf->SetSubject(Yii::t('export', 'all_amendments_title'));

try {
    \app\views\motion\LayoutHelper::printToPDF($pdf, $pdfLayout, $motion);

    foreach ($amendments as $amendment) {
        \app\views\amendment\LayoutHelper::printToPDF($pdf, $pdfLayout, $amendment);
    }
} catch (\Exception $e) {
    echo \Yii::t('base', 'err_unknown') . ': ' . Html::encode($e);
    die();
}

$pdf->Output($motion->getFilenameBase(true) . '_amendments.pdf', 'I');

die();
