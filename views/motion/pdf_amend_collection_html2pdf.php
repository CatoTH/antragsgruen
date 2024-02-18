<?php

use app\components\Tools;
use app\views\pdfLayouts\IPdfWriter;
use app\models\db\{Amendment, Motion, TexTemplate};
use app\views\amendment\{LayoutHelper as AmendmentLayoutHelper};
use app\views\motion\{LayoutHelper as MotionLayoutHelper};

/**
 * @var TexTemplate $texTemplate
 * @var Motion $motion
 * @var Amendment[] $amendments
 */

$pdf = new IPdfWriter();
$pdf->SetCreator(Yii::t('export', 'default_creator'));
$pdf->SetAuthor(Yii::t('export', 'default_creator'));
$pdf->SetTitle($motion->getTitleWithPrefix());
$pdf->SetSubject($motion->getTitleWithPrefix());
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdfData = MotionLayoutHelper::createPdfFromHtml($motion);
$bookmarkId = 'motion' . $motion->id;
Tools::appendPdfToPdf($pdf, $pdfData, $bookmarkId, $motion->getTitleWithPrefix());

$amendments = $motion->getVisibleAmendmentsSorted();
foreach ($amendments as $amendment) {
    $pdfData = AmendmentLayoutHelper::createPdfFromHtml($amendment);
    $bookmarkId = 'amendment' . $amendment->id;
    Tools::appendPdfToPdf($pdf, $pdfData, $bookmarkId, $amendment->getTitleWithPrefix());
}

$pdf->Output(Tools::sanitizeFilename($motion->getMyMotionType()->titlePlural, true) . '.pdf', 'I');
