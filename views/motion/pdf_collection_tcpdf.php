<?php

use app\models\db\{Amendment, IMotion, Motion};
use app\components\Tools;
use yii\helpers\Html;

/**
 * @var IMotion[] $imotions
 */

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
        } elseif (is_a($imotion, Amendment::class)) {
            \app\views\amendment\LayoutHelper::printToPDF($pdf, $pdfLayout, $imotion);
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . Html::encode($e);
    die();
}

$pdf->Output(Tools::sanitizeFilename($motionType->titlePlural, true) . '.pdf', 'I');

