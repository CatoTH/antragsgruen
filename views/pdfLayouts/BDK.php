<?php

namespace app\views\pdfLayouts;

use app\models\db\{Amendment, Motion};
use setasign\Fpdi\Tcpdf\Fpdi;
use yii\helpers\Html;

class BDK extends IPDFLayout
{
    public function createPDFClass(): IPdfWriter
    {
        $pdf = new BDKPDF();

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setCellHeightRatio(1.5);

        $pdf->SetMargins(25, 27, 25);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM - 5);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('dejavusans', '', 10);

        $pdf->setHtmlVSpace([
            'ul'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'li'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'ol'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'div'        => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'p'          => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'blockquote' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
        ]);

        $this->pdf = $pdf;

        return $pdf;
    }

    public static function printHeaderTable(\TCPDF $pdf, ?string $pdfIntroduction, string $tableContent): void
    {
        $title = str_replace("\n", '<br>', $pdfIntroduction);
        $pdf->SetY(35);
        if ($title) {
            $pdf->SetFont("helvetica", "", 13);
            $pdf->writeHTMLCell(185, 0, 10, 10, $title, 0, 1, false, true, 'R');
        }

        $pdf->SetFont("helvetica", "", 12);

        $pdf->setCellPaddings(2, 4, 2, 4);
        $pdf->writeHTMLCell(170, 0, 25, 35, $tableContent, 1, 1, false, true, 'L');

        $pdf->Ln(7);
        $pdf->setCellPaddings(0, 0, 0, 0);
    }

    public function printMotionHeader(Motion $motion): void
    {
        /** @var BDKPDF $pdf */
        $pdf = $this->pdf;

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->startPageGroup();
        $pdf->AddPage();

        $pdf->setDestination('motion' . $motion->id, 0, '');
        $pdf->Bookmark($motion->getTitleWithPrefix(), 0, 0, '', 'BI', [128,0,0], -1, '#motion' . $motion->id);

        $pdf->setMotionTitle($motion->getFormattedTitlePrefix(), $motion->title);
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);

        $title = $motion->getMyMotionType()->getSettingsObj()->pdfIntroduction;
        if ($title === \Yii::t('export', 'Initiators')) { // The default setting is just a placeholder
            $title = '';
        }

        $motionData = '<span style="font-size: 20px; font-weight: bold">';
        $motionData .= Html::encode($motion->getFormattedTitlePrefix()) . ' </span>';
        $motionData .= '<span style="font-size: 16px; font-weight: bold;">';
        $motionData .= Html::encode($motion->title) . '</span>';
        $motionData .= '<br><br>';

        $motionData .= '<table>';
        foreach ($motion->getDataTable() as $key => $val) {
            $motionData .= '<tr><th style="width: 28%;">' . Html::encode($key) . ':</th>';
            $motionData .= '<td>' . Html::encode($val) . '</td></tr>';
        }

        $motionData .= '</table>';

        BDK::printHeaderTable($this->pdf, $title, $motionData);
    }

    public function printAmendmentHeader(Amendment $amendment): void
    {
        /** @var BDKPDF $pdf */
        $pdf = $this->pdf;

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->startPageGroup();
        $pdf->AddPage();

        $pdf->setDestination('amendment' . $amendment->id, 0, '');
        $pdf->Bookmark($amendment->getTitleWithPrefix(), 0, 0, '', 'BI', [128,0,0], -1, '#amendment' . $amendment->id);

        $pdf->setMotionTitle($amendment->getFormattedTitlePrefix(), '');
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);


        $title = $amendment->getMyMotionType()->getSettingsObj()->pdfIntroduction;

        $amendmentData = '<span style="font-size: 16px; font-weight: bold;">';
        $amendmentData .= Html::encode($amendment->getTitle()) . '</span>';
        $amendmentData .= '<br><br>';

        $amendmentData .= '<table>';
        $amendmentData .= '<tr><th style="width: 28%;">' . Html::encode(\Yii::t('motion', 'initiators_head')) . '</th>';
        $amendmentData .= '<td>' . Html::encode($amendment->getInitiatorsStr()) . '</td></tr>';

        $amendmentData .= '</table>';

        BDK::printHeaderTable($this->pdf, $title, $amendmentData);
    }
}
