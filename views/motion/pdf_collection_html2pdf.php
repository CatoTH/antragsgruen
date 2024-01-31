<?php

use app\models\db\{Amendment, IMotion, Motion};
use app\components\Tools;
use app\views\pdfLayouts\IPdfWriter;
use app\views\amendment\{LayoutHelper as AmendmentLayoutHelper};
use app\views\motion\{LayoutHelper as MotionLayoutHelper};
use setasign\Fpdi\PdfParser\StreamReader;

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

    $pageCount = $pdf->setSourceFile(StreamReader::createByString($pdfData));

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $page = $pdf->ImportPage($pageNo);
        $dim  = $pdf->getTemplatesize($page);
        $pdf->AddPage($dim['width'] > $dim['height'] ? 'L' : 'P', [$dim['width'], $dim['height']], false);

        if ($pageNo === 1) {
            $pdf->setDestination($bookmarkId, 0, '');
            $pdf->Bookmark($imotion->getTitleWithPrefix(), 0, 0, '', '', [128,0,0], -1, '#' . $bookmarkId);
        }

        $pdf->useTemplate($page);
    }
}


$pdf->Output(Tools::sanitizeFilename($motionType->titlePlural, true) . '.pdf', 'I');
