<?php

namespace app\views\pdfLayouts;

use app\models\db\Amendment;
use app\models\db\IMotionSection;
use app\models\db\Motion;
use app\models\settings\AntragsgruenApp;
use TCPDF;
use yii\helpers\Html;

class DBJR extends IPDFLayout
{

    private $headerlogo = array();

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
        $fontsize = 35;

        $dim = $pdf->getPageDimensions();

        if ($site->getAbsolutePdfLogo()) {
            if (empty($this->headerlogo)) {
                $this->headerlogo['dim'] = getimagesize($site->getAbsolutePdfLogo());
                $this->headerlogo['w'] = 50;
                $this->headerlogo['scale'] = $this->headerlogo['w'] / $this->headerlogo['dim'][0];
                $this->headerlogo['h'] = $this->headerlogo['dim'][1] * $this->headerlogo['scale'];
                $this->headerlogo['x'] = $dim['wk'] - $dim['rm'] - $this->headerlogo['w'];
                if ($this->headerlogo['h'] + $abs < $dim['tm'] / 2) {
                    $this->headerlogo['y'] = $dim['tm'] - $this->headerlogo['h'] - $abs;
                }
                else {
                    $this->headerlogo['y'] = $dim['tm'];
                }
            }
            $position = 'L';
            $pdf->setJPEGQuality(100);
            $pdf->Image($site->getAbsolutePdfLogo(), $this->headerlogo['x'], $this->headerlogo['y'], $this->headerlogo['w'], $this->headerlogo['h']);
            $pdf->setY($this->headerlogo['y'] + $this->headerlogo['h'] + $abs);
        }

        $pdf->SetFont('helvetica', 'B', $fontsize);
        $pdf->SetTextColor(40, 40, 40, 40);
        //$pdf->SetXY($left, $wraptop);
        $pdf->Write(0, mb_strtoupper($motion->motionType->titleSingular, 'UTF-8') . "\n");

        $pdf->SetTextColor(100, 100, 100, 100);
        $wraptop = $pdf->getY()+$abs;
        $pdf->SetXY($left, $wraptop);

        $pdf->SetFont('helvetica', 'I', 11);
        $intro = $motion->getMyConsultation()->getSettings()->pdfIntroduction;
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
        $fontsize = 35;

        $dim = $pdf->getPageDimensions();

        if ($site->getAbsolutePdfLogo()) {
            if (empty($this->headerlogo)) {
                $this->headerlogo['dim'] = getimagesize($site->getAbsolutePdfLogo());
                $this->headerlogo['w'] = 50;
                $this->headerlogo['scale'] = $this->headerlogo['w'] / $this->headerlogo['dim'][0];
                $this->headerlogo['h'] = $this->headerlogo['dim'][1] * $this->headerlogo['scale'];
                $this->headerlogo['x'] = $dim['wk'] - $dim['rm'] - $this->headerlogo['w'];
                if ($this->headerlogo['h'] + $abs < $dim['tm'] / 2) {
                    $this->headerlogo['y'] = $dim['tm'] - $this->headerlogo['h'] - $abs;
                }
                else {
                    $this->headerlogo['y'] = $dim['tm'];
                }
            }
            $position = 'L';
            $pdf->setJPEGQuality(100);
            $pdf->Image($site->getAbsolutePdfLogo(), $this->headerlogo['x'], $this->headerlogo['y'], $this->headerlogo['w'], $this->headerlogo['h']);
            $pdf->setY($this->headerlogo['y'] + $this->headerlogo['h'] + $abs);
        }

        $pdf->SetFont('helvetica', 'B', '35');
        $pdf->SetTextColor(40, 40, 40, 40);
        $pdf->Write(0, mb_strtoupper('Änderungsantrag ' . $amendment->titlePrefix, 'UTF-8') . "\n");

        $pdf->SetTextColor(100, 100, 100, 100);
        $wraptop = $pdf->getY()+$abs;
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
     * @return \FPDI
     */
    public function createPDFClass()
    {
        $pdf = new DBJRPDF($this);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        $pdf->SetMargins(25, 40, 25);
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
