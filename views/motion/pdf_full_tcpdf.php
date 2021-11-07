<?php

use app\models\db\{Amendment, IMotion, Motion};
use yii\helpers\Html;

/**
 * @var IMotion[] $imotions
 */

if (count($imotions) === 0) {
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->Output('Motions.pdf', 'I');

    die();
}

$motionType = $imotions[0]->getMyMotionType();
$pdfLayout  = $motionType->getPDFLayoutClass();
$pdf        = $pdfLayout->createPDFClass();

// set document information
$title = str_replace('%TITLE%', $motionType->titlePlural, Yii::t('export', 'all_motions_title'));
$pdf->SetCreator(Yii::t('export', 'default_creator'));
$pdf->SetAuthor(Yii::t('export', 'default_creator'));
$pdf->SetTitle($title);
$pdf->SetSubject($title);


try {
    foreach ($imotions as $imotion) {
        if (is_a($imotion, Motion::class)) {
            \app\views\motion\LayoutHelper::printToPDF($pdf, $pdfLayout, $imotion);

            $amendments = $imotion->getVisibleAmendmentsSorted();
            foreach ($amendments as $amendment) {
                \app\views\amendment\LayoutHelper::printToPDF($pdf, $pdfLayout, $amendment);
            }
        }
        if (is_a($imotion, Amendment::class)) {
            \app\views\amendment\LayoutHelper::printToPDF($pdf, $pdfLayout, $imotion);
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . Html::encode($e);
    die();
}

$pdf->Output('Motions-with-amendments.pdf', 'I');
