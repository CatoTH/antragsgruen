<?php

use app\models\db\{Amendment, IMotion, Motion};
use app\components\Tools;
use app\views\amendment\{LayoutHelper as AmendmentLayoutHelper};
use app\views\motion\{LayoutHelper as MotionLayoutHelper};
use app\views\pdfLayouts\IPdfWriter;

/**
 * @var IMotion[] $imotions
 */

$pdf = new IPdfWriter();
$pdf->SetCreator(Yii::t('export', 'default_creator'));
$pdf->SetAuthor(Yii::t('export', 'default_creator'));
$pdf->SetTitle($imotions[0]->getMyMotionType()->titlePlural);
$pdf->SetSubject($imotions[0]->getMyMotionType()->titlePlural);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

foreach ($imotions as $imotion) {
    if (is_a($imotion, Motion::class)) {
        $pdfData = MotionLayoutHelper::createPdfFromHtml($imotion);
        $bookmarkId = 'motion' . $imotion->id;
        Tools::appendPdfToPdf($pdf, $pdfData, $bookmarkId, $imotion->getTitleWithPrefix());

        $amendments = $imotion->getVisibleAmendmentsSorted();
        foreach ($amendments as $amendment) {
            $pdfData = AmendmentLayoutHelper::createPdfFromHtml($amendment);
            $bookmarkId = 'amendment' . $amendment->id;
            Tools::appendPdfToPdf($pdf, $pdfData, $bookmarkId, $amendment->getTitleWithPrefix());
        }
    }
    if (is_a($imotion, Amendment::class)) {
        $pdfData = AmendmentLayoutHelper::createPdfFromHtml($imotion);
        $bookmarkId = 'amendment' . $imotion->id;
        Tools::appendPdfToPdf($pdf, $pdfData, $bookmarkId, $imotion->getTitleWithPrefix());
    }
}

$pdf->Output(Tools::sanitizeFilename($imotions[0]->getMyMotionType()->titlePlural, true) . '.pdf', 'I');
