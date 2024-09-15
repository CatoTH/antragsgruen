<?php

/**
 * @var \app\models\mergeAmendments\Init $form
 */

$motionType = $form->motion->getMyMotionType();
$pdfLayout  = $motionType->getPDFLayoutClass();
if (!$pdfLayout) {
    $pdfLayout = new \app\views\pdfLayouts\BDK($motionType);
}
$pdf        = $pdfLayout->createPDFClass();

// set document information
$title = $form->motion->title;
$pdf->SetCreator(Yii::t('export', 'default_creator'));
$pdf->SetAuthor(Yii::t('export', 'default_creator'));
$pdf->SetTitle($title);
$pdf->SetSubject($title);

\app\views\motion\LayoutHelper::printMotionWithEmbeddedAmendmentsToPdf($form, $pdfLayout, $pdf);

$pdf->Output(\app\components\Tools::sanitizeFilename($form->motion->getTitleWithPrefix(), true) . '.pdf', 'I');
