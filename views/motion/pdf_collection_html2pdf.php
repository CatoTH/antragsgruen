<?php

use app\models\db\{Amendment, IMotion, Motion};
use app\components\Tools;
use app\views\pdfLayouts\IPdfWriter;
use app\views\amendment\{LayoutHelper as AmendmentLayoutHelper};
use app\views\motion\{LayoutHelper as MotionLayoutHelper};

/**
 * @var IMotion[] $imotions
 */

$motionType = $imotions[0]->getMyMotionType();

$pdf = new IPdfWriter();
$title = str_replace('%TITLE%', $motionType->titlePlural, Yii::t('export', 'all_motions_title'));
$pdf->SetCreator(Yii::t('export', 'default_creator'));
$pdf->SetAuthor(Yii::t('export', 'default_creator'));
$pdf->SetTitle($title);
$pdf->SetSubject($title);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);


foreach ($imotions as $imotion) {
    if (is_a($imotion, Motion::class)) {
        $pdfData = MotionLayoutHelper::createPdfFromHtml($imotion);
        $bookmarkId = 'motion' . $imotion->id;
    } else {
        /** @var Amendment $imotion */
        $pdfData = AmendmentLayoutHelper::createPdfFromHtml($imotion);
        $bookmarkId = 'amendment' . $imotion->id;
    }

    Tools::appendPdfToPdf($pdf, $pdfData, $bookmarkId, $imotion->getTitleWithPrefix());
}


$pdf->Output(Tools::sanitizeFilename($motionType->titlePlural, true) . '.pdf', 'I');
