<?php

namespace app\views\pdfLayouts;

use app\models\db\{Amendment, Motion};
use yii\helpers\Html;

class DBJR extends IPDFLayout
{
    public function printMotionHeader(Motion $motion): void
    {
        $pdf = $this->pdf;

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->startPageGroup();
        $pdf->AddPage();

        $pdf->setDestination('motion' . $motion->id, 0, '');
        $pdf->Bookmark($motion->getTitleWithPrefix(), 0, 0, '', 'BI', array(128,0,0), -1, '#motion' . $motion->id);

        $left     = 23.5;
        $abs      = 5;
        $fontsize = 30;

        $this->setHeaderLogo($motion->getMyConsultation(), $abs, 50, null);
        if ($this->headerlogo) {
            $logo = $this->headerlogo;
            $pdf->setJPEGQuality(100);
            $pdf->Image('@' . $logo['data'], $logo['x'], $logo['y'], $logo['w'], $logo['h']);
            $pdf->setY($logo['y'] + $logo['h'] + $abs);
        }

        $pdf->SetFont('helvetica', 'B', $fontsize);
        $pdf->SetTextColor(40, 40, 40, 40);
        //$pdf->SetXY($left, $wraptop);
        $pdf->Write(0, mb_strtoupper($motion->motionType->titleSingular, 'UTF-8') . "\n");

        $pdf->SetTextColor(100, 100, 100, 100);
        $wraptop = $pdf->getY() + $abs;
        $pdf->SetXY($left, $wraptop);

        $pdf->SetFont('helvetica', 'I', 11);
        $intro = $motion->getMyMotionType()->getSettingsObj()->pdfIntroduction;
        if ($intro) {
            $pdf->MultiCell(160, 0, $intro, 0, 'L');
            $pdf->Ln(3);
        }

        $data = $motion->getDataTable();
        foreach ($data as $key => $val) {
            $pdf->SetX($left);
            $pdf->MultiCell(42, 0, $key . ':', 0, 'L', false, 0);
            $pdf->MultiCell(120, 0, $val, 0, 'L');
            $pdf->Ln(5);
        }

        $pdf->Ln(9);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->writeHTML('<h3>' . Html::encode($motion->getTitleWithPrefix()) . '</h3>');

        $pdf->SetFont('helvetica', '', 12);
    }

    public function printAmendmentHeader(Amendment $amendment): void
    {
        $pdf            = $this->pdf;

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->startPageGroup();
        $pdf->AddPage();

        $pdf->setDestination('amendment' . $amendment->id, 0, '');
        $pdf->Bookmark($amendment->getTitleWithPrefix(), 0, 0, '', 'BI', [128,0,0], -1, '#amendment' . $amendment->id);

        $left           = 23.5;
        $abs            = 5;
        $title1Fontsize = 15;
        $title2Fontsize = 25;

        $dim = $pdf->getPageDimensions();

        $logo = $amendment->getMyConsultation()->getAbsolutePdfLogo();
        if ($logo) {
            if (empty($this->headerlogo)) {
                //$this->headerlogo['dim']   = getimagesize($site->getAbsolutePdfLogo());
                $headerlogo = [];
                $headerlogo['w']     = 50.0;
                $headerlogo['scale'] = $headerlogo['w'] / $logo->width;
                $headerlogo['h']     = $logo->height * $headerlogo['scale'];
                $headerlogo['x']     = $dim['wk'] - $dim['rm'] - $headerlogo['w'];
                $headerlogo['data']  = $logo->data;
                if ($headerlogo['h'] + $abs < $dim['tm'] / 2) {
                    $headerlogo['y'] = $dim['tm'] - $headerlogo['h'] - $abs;
                } else {
                    $headerlogo['y'] = $dim['tm'];
                }
                $this->headerlogo = $headerlogo;
            }


            $headerlogo = $this->headerlogo;
            $pdf->setJPEGQuality(100);
            $pdf->Image('@' . $logo->data, $headerlogo['x'], $headerlogo['y'], $headerlogo['w'], $headerlogo['h']);
            $pdf->setY($headerlogo['y'] + $headerlogo['h'] + $abs);
        }

        $pdf->SetTextColor(100, 100, 100, 100);
        $pdf->SetFont('helvetica', 'I', 11);
        $pdf->SetXY($left, $pdf->getY());
        $pdf->Ln(3);
        $intro = $amendment->getMyMotionType()->getSettingsObj()->pdfIntroduction;
        if ($intro) {
            $pdf->MultiCell(0, 0, trim($intro), 0, 'L');
            $pdf->Ln(3);
        }

        $pdf->SetTextColor(40, 40, 40, 40);
        $pdf->SetFont('helvetica', 'B', $title1Fontsize);
        $pdf->MultiCell(0, 0, trim($amendment->getMyMotion()->getTitleWithPrefix()), 0, 'L');
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', $title2Fontsize);
        $pdf->Write(0, mb_strtoupper(\Yii::t('amend', 'amendment') . ' ' . $amendment->getFormattedTitlePrefix(), 'UTF-8') . "\n");
        $pdf->Ln(3);

        $pdf->SetX($left);
        $pdf->SetTextColor(100, 100, 100, 100);
        $pdf->SetFont('helvetica', 'I', 11);
        $data = $amendment->getDataTable();
        foreach ($data as $key => $val) {
            $pdf->SetX($left);
            $pdf->MultiCell(42, 0, $key . ':', 0, 'L', false, 0);
            $pdf->MultiCell(120, 0, $val, 0, 'L');
            $pdf->Ln(5);
        }

        $pdf->SetFont('helvetica', '', 12);
        $pdf->ln(7);
    }

    public function createPDFClass(): IPdfWriter
    {
        $pdf = new DBJRPDF($this);

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setCellHeightRatio(1.5);

        $pdf->SetMargins(23, 40, 23);
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
}
