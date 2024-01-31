<?php

use app\models\db\Amendment;
use app\components\Tools;
use app\views\pdfLayouts\IPdfWriter;
use app\views\amendment\{LayoutHelper as AmendmentLayoutHelper};
use setasign\Fpdi\PdfParser\StreamReader;

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

    $pageCount = $pdf->setSourceFile(StreamReader::createByString($pdfData));

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $page = $pdf->ImportPage($pageNo);
        $dim  = $pdf->getTemplatesize($page);
        $pdf->AddPage($dim['width'] > $dim['height'] ? 'L' : 'P', [$dim['width'], $dim['height']], false);

        if ($pageNo === 1) {
            $pdf->setDestination($bookmarkId, 0, '');
            $pdf->Bookmark($amendment->getTitleWithPrefix(), 0, 0, '', '', [128,0,0], -1, '#' . $bookmarkId);
        }

        $pdf->useTemplate($page);
    }
}

$pdf->Output(Tools::sanitizeFilename($motionType->titlePlural, true) . '.pdf', 'I');
