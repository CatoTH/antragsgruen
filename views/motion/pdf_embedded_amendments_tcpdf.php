<?php

/**
 * @noinspection HtmlDeprecatedAttribute
 *
 * @var \app\models\mergeAmendments\Init $form
 */

use yii\helpers\Html;

$motionType = $form->motion->getMyMotionType();
$pdfLayout  = $motionType->getPDFLayoutClass();
$pdf        = $pdfLayout->createPDFClass();

// set document information
$title = $form->motion->title;
$pdf->SetCreator(Yii::t('export', 'default_creator'));
$pdf->SetAuthor(Yii::t('export', 'default_creator'));
$pdf->SetTitle($title);
$pdf->SetSubject($title);

$pdfLayout->printMotionHeader($form->motion);

$pdf->Ln(5);
$amendmentsHtml = '<table border="1" cellpadding="5"><tr><td><h2>' . Yii::t('export', 'amendments') . '</h2>';
foreach ($form->motion->getVisibleAmendments(false, false) as $amendment) {
    $amendmentsHtml .= '<div><strong>' . Html::encode($amendment->titlePrefix) . '</strong>: ' . Html::encode($amendment->getInitiatorsStr()) . '</div>';
}
if (count($form->motion->getVisibleAmendments(false, false)) === 0) {
    $amendmentsHtml .= '<em>' . Yii::t('export', 'amendments_none') . '</em>';
}
$amendmentsHtml .= '</td></tr></table>';
$pdf->writeHTML($amendmentsHtml, true, false, false, true);
$pdf->Ln(5);

\app\views\motion\LayoutHelper::printMotionWithEmbeddedAmendmentsToPdf($form, $pdfLayout, $pdf);

$pdf->Output(\app\components\Tools::sanitizeFilename($form->motion->getTitleWithPrefix(), true) . '.pdf', 'I');

die();
