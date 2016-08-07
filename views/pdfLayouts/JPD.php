<?php

namespace app\views\pdfLayouts;

use app\models\db\Amendment;
use app\models\db\IMotionSection;
use app\models\db\Motion;
use app\models\settings\AntragsgruenApp;
use TCPDF;
use yii\helpers\Html;

class JPD extends IPDFLayout
{
    /**
     * @param Motion $motion
     */
    public function printMotionHeader(Motion $motion)
    {
        $pdf = $this->pdf;
        /** @var AntragsgruenApp $site */
        $site = \yii::$app->params;
        $left = 23.5;
        $abs = 5;
        $fontsize = 20;

        $pdf->SetFont('helvetica', 'B', $fontsize);
        $pdf->SetTextColor(100, 85, 0, 0);
        //$pdf->SetXY($left, $wraptop);
        $pdf->Write(0, mb_strtoupper($motion->motionType->titleSingular, 'UTF-8') . "\n");

        $wraptop = $pdf->getY()+$abs;
        $pdf->SetXY($left, $wraptop);

        $pdf->SetFont('helvetica', 'I', 11);
        $intro = $motion->getConsultation()->getSettings()->pdfIntroduction;
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

        $pdf->SetTextColor(100, 0, 80, 10);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->writeHTML('<h3>' . Html::encode($motion->getTitleWithPrefix()) . '</h3>');

        $pdf->SetTextColor(100, 100, 100, 100);

        $pdf->SetFont('helvetica', '', 12);
    }

    /**
     * @param Amendment $amendment
     */
    public function printAmendmentHeader(Amendment $amendment)
    {
        $pdf = $this->pdf;
        /** @var AntragsgruenApp $site */
        $site = \yii::$app->params;
        $left = 23.5;
        $abs = 5;
        $fontsize = 20;

        $pdf->SetFont('helvetica', 'B', $fontsize);
        $pdf->SetTextColor(40, 40, 40, 40);
        //$pdf->SetXY($left, $wraptop);
        $pdf->Write(0, 'Änderungsantrag ' . $amendment->titlePrefix . "\n");

        $wraptop = $pdf->getY() + $abs;
        $pdf->SetTextColor(100, 100, 100, 100);
        $pdf->SetXY($left, $wraptop);

        $pdf->SetFont('helvetica', 'I', 11);
        $intro = $amendment->getMyConsultation()->getSettings()->pdfIntroduction;
        if ($intro) {
            $pdf->MultiCell(160, 0, $intro, 0, 'L');
            $pdf->Ln(3);
        }

        $pdf->SetX($left);
        $pdf->MultiCell(42, 0, 'Antrag:', 0, 'L', false, 0);
        $pdf->MultiCell(120, 0, $amendment->getMyMotion()->getTitleWithPrefix(), 0, 'L');
        $pdf->Ln(5);
        $data = $amendment->getDataTable();
        foreach ($data as $key => $val) {
            $pdf->SetX($left);
            $pdf->MultiCell(42, 0, $key . ':', 0, 'L', false, 0);
            $pdf->MultiCell(120, 0, $val, 0, 'L');
            $pdf->Ln(5);
        }

        $pdf->Ln(9);

        $pdf->SetFont('helvetica', '', 12);
        $pdf->ln(7);
    }

    /**
     * @return TCPDF
     */
    public function createPDFClass()
    {
        $pdf = new JPDPDF($this);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        $pdf->SetMargins(25, 20, 25);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM - 5);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('dejavusans', '', 10);

        $this->pdf = $pdf;

        return $pdf;
    }

    /**
     * @param IMotionSection $section
     * @return bool
     */
    public function isSkippingSectionTitles(IMotionSection $section)
    {
        if ($section->getSettings()->title == \Yii::t('motion', 'motion_text')) {
            return true;
        }
        return false;

    }
}
