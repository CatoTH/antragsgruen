<?php

use app\models\db\Amendment;
use app\components\Tools;
use app\views\pdfLayouts\IPdfWriter;
use app\views\amendment\{LayoutHelper as AmendmentLayoutHelper};

/**
 * @var Amendment[] $amendments
 */

$motionType = $amendments[0]->getMyMotionType();

$pdf = new IPdfWriter();
$pdf->SetCreator(Yii::t('export', 'default_creator'));
$pdf->SetAuthor(Yii::t('export', 'default_creator'));
$pdf->SetTitle(Yii::t('export', 'all_amendments_title'));
$pdf->SetSubject(Yii::t('export', 'all_amendments_title'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);


foreach ($amendments as $amendment) {
    $pdfData = AmendmentLayoutHelper::createPdfFromHtml($amendment);
    $bookmarkId = 'amendment' . $amendment->id;

    Tools::appendPdfToPdf($pdf, $pdfData, $bookmarkId, $amendment->getTitleWithPrefix());
}

$pdf->Output(Tools::sanitizeFilename($motionType->titlePlural, true) . '.pdf', 'I');
