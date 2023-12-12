<?php

namespace app\plugins\egp\pdf;

use app\models\db\{Amendment, Consultation, Motion};
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};

class Egp extends IPDFLayout
{
    protected function setHeaderLogo(Consultation $consultation, int $abs, ?float $maxWidth, ?float $maxHeight): void
    {
        if ($consultation->urlPath === '36thEGPcouncil') {
            $logo = file_get_contents(__DIR__ . '/../assets/council-2022-06.png');
            $width = 904;
            $height = 398;
        } elseif ($consultation->urlPath === '6thEGPcongress') {
            $logo = file_get_contents(__DIR__ . '/../assets/congress-2022-12.png');
            $width = 678;
            $height = 298;
        } elseif ($consultation->urlPath === 'spring2022_council') {
            $logo = file_get_contents(__DIR__ . '/../assets/council-2022-06.png');
            $width = 905;
            $height = 398;
        } elseif ($consultation->urlPath === 'winter2021_council') {
            $logo = file_get_contents(__DIR__ . '/../assets/council-2021-12.png');
            $width = 678;
            $height = 298;
        } elseif ($consultation->urlPath === 'spring2021_council') {
            $logo = file_get_contents(__DIR__ . '/../assets/council-2021-05.png');
            $width = 678;
            $height = 298;
        } elseif ($consultation->urlPath === 'autumn2020_council') {
            $logo = file_get_contents(__DIR__ . '/../assets/council-2020-12.png');
            $width = 678;
            $height = 298;
        } elseif ($consultation->urlPath === '38thEGPCongress') {
            $logo = file_get_contents(__DIR__ . '/../assets/congress-2023-12.png');
            $width = 678;
            $height = 298;
        } elseif ($consultation->urlPath === 'Electoralcongress2024') {
            $logo = file_get_contents(__DIR__ . '/../assets/congress-2024-02.png');
            $width = 678;
            $height = 298;
        } else {
            $logo = file_get_contents(__DIR__ . '/../assets/council-2020-06.png');
            $width = 2194;
            $height = 1178;
        }

        $dim = $this->pdf->getPageDimensions();

        $scaleWidth = $scaleHeight = 1.0;
        if ($maxWidth && $width > $maxWidth) {
            $scaleWidth = $maxWidth / $width;
        }
        if ($maxHeight && $height > $maxHeight) {
            $scaleHeight = $maxHeight / $height;
        }

        $headerlogo = [];
        $headerlogo['scale'] = min($scaleHeight, $scaleWidth);
        $headerlogo['w'] = $width * $headerlogo['scale'];
        $headerlogo['h'] = $height * $headerlogo['scale'];
        $headerlogo['x'] = $dim['wk'] - $dim['rm'] - $headerlogo['w'];
        $headerlogo['data'] = (string)$logo;
        if ($headerlogo['h'] + $abs < $dim['tm'] / 2) {
            $headerlogo['y'] = $dim['tm'] - $headerlogo['h'] - $abs;
        } else {
            $headerlogo['y'] = $dim['tm'];
        }
        $this->headerlogo = $headerlogo;
    }

    public function printMotionHeader(Motion $motion): void
    {
        $pdf = $this->pdf;

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->startPageGroup();
        $pdf->AddPage();

        $this->setHeaderLogo($motion->getMyConsultation(), 32, 50, 35);
        if ($this->headerlogo) {
            $logo = $this->headerlogo;
            $pdf->setJPEGQuality(100);
            $pdf->Image('@' . $logo['data'], 22, 32, $logo['w'], $logo['h']);
        }

        $revName = \Yii::t('export', 'draft');
        $pdf->SetFont('roboto', 'B', 25);
        $width = (float)$pdf->GetStringWidth($revName, 'roboto', 'I', 25) + 3.1;

        if ($width < 35) {
            $width = 35;
        }

        $pdf->SetXY(192 - $width, 37, true);
        $this->pdf->MultiCell($width, 21, $revName, 0, 'C', false, 1, null, null, true, 0, false, true, 21, 'M');

        $pdf->SetY(85);
        $intro = $this->motionType->getSettingsObj()->pdfIntroduction;
        if ($intro === \Yii::t('export', 'introduction')) {  // The default setting is just a placeholder
            $intro = null;
        }
        if ($intro) {
            $pdf->SetX(24);
            $pdf->SetFont('roboto', 'B', 12);
            $pdf->MultiCell(160, 13, $intro, 0, 'C');
            $pdf->Ln(7);
        }

        $pdf->SetX(12);

        $pdf->SetFont('roboto', 'B', 12);
        $pdf->MultiCell(12, 0, '', 0, 'L', false, 0);

        $pdf->MultiCell(50, 0, \Yii::t('export', 'title') . ':', 0, 'L', false, 0);
        $pdf->SetFont('roboto', 'B', 12);
        $pdf->MultiCell(100, 0, $motion->title, 0, 'L');

        $pdf->Ln(5);
        $pdf->SetX(12);

        $initiators = [];
        foreach ($motion->getInitiators() as $initiator) {
            $initiators[] = $initiator->organization;
        }
        $pdf->SetFont('roboto', 'B', 12);
        $pdf->MultiCell(12, 0, '', 0, 'L', false, 0);
        $pdf->MultiCell(50, 0, \Yii::t('export', 'Initiators') . ':', 0, 'L', false, 0);
        $pdf->SetFont('roboto', '', 12);
        $pdf->MultiCell(120, 0, implode(', ', $initiators), 0, 'L');

        $pdf->Ln(9);
    }

    public function printAmendmentHeader(Amendment $amendment): void
    {
        $pdf = $this->pdf;
        $settings = $this->motionType->getConsultation()->getSettings();

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->startPageGroup();
        $pdf->AddPage();

        $this->setHeaderLogo($amendment->getMyConsultation(), 32, 50, 35);
        if ($this->headerlogo) {
            $logo = $this->headerlogo;
            $pdf->setJPEGQuality(100);
            $pdf->Image('@' . $logo['data'], 22, 32, $logo['w'], $logo['h']);
        }


        $pdf->SetY(85);
        $intro = $this->motionType->getSettingsObj()->pdfIntroduction;
        if ($intro === \Yii::t('export', 'introduction')) {  // The default setting is just a placeholder
            $intro = null;
        }
        if ($intro) {
            $pdf->SetX(24);
            $pdf->SetFont('roboto', 'B', 12);
            $pdf->MultiCell(160, 13, $intro, 0, 'C');
            $pdf->Ln(7);
        }


        $pdf->SetX(12);

        $pdf->SetFont('roboto', 'B', 12);
        $pdf->MultiCell(12, 0, '', 0, 'L', false, 0);

        $pdf->MultiCell(50, 0, \Yii::t('export', 'title') . ':', 0, 'L', false, 0);
        $pdf->SetFont('roboto', 'B', 12);
        $pdf->MultiCell(100, 0, $amendment->getTitle(), 0, 'L');

        $pdf->Ln(5);
        $pdf->SetX(12);

        $initiators = [];
        foreach ($amendment->getInitiators() as $initiator) {
            $initiators[] = $initiator->organization;
        }
        $pdf->SetFont('roboto', 'B', 12);
        $pdf->MultiCell(12, 0, '', 0, 'L', false, 0);
        $pdf->MultiCell(50, 0, \Yii::t('export', 'Initiators') . ':', 0, 'L', false, 0);
        $pdf->SetFont('roboto', '', 12);
        $pdf->MultiCell(120, 0, implode(', ', $initiators), 0, 'L');

        $pdf->Ln(9);
    }

    public function createPDFClass(): IPdfWriter
    {
        $pdf = new EgpPdf($this);

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setCellHeightRatio(1.5);

        $pdf->SetMargins(25, 40, 25);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM - 5);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('dejavusans', '', 10);

        $pdf->setHtmlVSpace([
            'ul' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'ol' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'li' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'div' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'p' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'blockquote' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
        ]);

        $this->pdf = $pdf;

        return $pdf;
    }
}
