<?php

use app\models\db\{Amendment, Motion};
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var Amendment[] $amendments
 */

$pdfLayout = $motion->motionType->getPDFLayoutClass();
$pdf       = $pdfLayout->createPDFClass();

// set document information
$pdf->SetCreator(Yii::t('export', 'default_creator'));
$pdf->SetAuthor(Yii::t('export', 'default_creator'));
$pdf->SetTitle(Yii::t('export', 'all_amendments_title'));
$pdf->SetSubject(Yii::t('export', 'all_amendments_title'));

try {
    \app\views\motion\LayoutHelper::printToPDF($pdf, $pdfLayout, $motion);

    foreach ($amendments as $amendment) {
        \app\views\amendment\LayoutHelper::printToPDF($pdf, $pdfLayout, $amendment);
    }
} catch (Exception $e) {
    echo Yii::t('base', 'err_unknown') . ': ' . Html::encode($e);
    die();
}

$pdf->Output($motion->getFilenameBase(true) . '_amendments.pdf', 'I');
