<?php

namespace app\views\pdfLayouts;

use app\models\db\{Amendment, Motion};

class ByLDK extends IPDFLayout
{
    public function printMotionHeader(Motion $motion): void
    {
        $pdf      = $this->pdf;
        $settings = $this->motionType->getConsultation()->getSettings();

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->startPageGroup();
        $pdf->AddPage();

        $pdf->setDestination('motion' . $motion->id, 0, '');
        $pdf->Bookmark($motion->getTitleWithPrefix(), 0, 0, '', 'BI', [128,0,0], -1, '#motion' . $motion->id);

        $this->setHeaderLogo($motion->getMyConsultation(), 32, 50, 35);
        if ($this->headerlogo) {
            $logo = $this->headerlogo;
            $pdf->setJPEGQuality(100);
            $pdf->Image('@' . $logo['data'], 22, 32, $logo['w'], $logo['h']);
        }

        if (!$settings->hideTitlePrefix) {
            $revName = $motion->getFormattedTitlePrefix();
            if (grapheme_strlen($revName) > 25) {
                $revName = grapheme_substr($revName, 0, 24) . '…';
            }
            if ($revName === '') {
                $revName = \Yii::t('export', 'draft');
                $pdf->SetFont('helvetica', 'I', 25);
                $width = (float)$pdf->GetStringWidth($revName, 'helvetica', 'I', 25) + 3.1;
            } else {
                $pdf->SetFont('helvetica', 'B', 25);
                $width = (float)$pdf->GetStringWidth($revName, 'helvetica', 'B', 25) + 3.1;
            }
            if ($width < 35) {
                $width = 35;
            }

            $pdf->SetXY(192 - $width, 37, true);
            $borderStyle = ['width' => 3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [150, 150, 150]];
            $this->pdf->MultiCell(
                $width,
                21,
                $revName,
                ['LTRB' => $borderStyle],
                'C',
                false,
                1,
                null,
                null,
                true,
                0,
                false,
                true,
                21, // defaults
                'M'
            );
        }

        $str = $motion->motionType->titleSingular;
        $pdf->SetFont('helvetica', 'B', 25);
        $width = (float)$pdf->GetStringWidth($str);

        $pdf->SetXY((210 - $width) / 2, 60);
        $pdf->Write(20, $str);
        $pdf->SetLineStyle(
            [
                'width' => 3,
                'color' => array(150, 150, 150),
            ]
        );
        $pdf->Line((210 - $width) / 2, 78, (210 + $width) / 2, 78);

        $pdf->SetY(90);
        $intro = $this->motionType->getSettingsObj()->pdfIntroduction;
        if ($intro === \Yii::t('export', 'introduction')) {  // The default setting is just a placeholder
            $intro = null;
        }
        if ($intro) {
            $pdf->SetX(24);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->MultiCell(160, 13, $intro, 0, 'C');
            $pdf->Ln(7);
        }

        $pdf->SetX(12);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->MultiCell(12, 0, '', 0, 'L', false, 0);
        $pdf->MultiCell(50, 0, \Yii::t('export', 'Initiators') . ':', 0, 'L', false, 0);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(120, 0, $motion->getInitiatorsStr(), 0, 'L');
        $pdf->Ln(5);

        if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports && $motion->proposalStatus !== null && $motion->isProposalPublic()) {
            $pdf->SetX(12);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->MultiCell(12, 0, '', 0, 'L', false, 0);
            $pdf->MultiCell(50, 0, \Yii::t('export', 'proposed_procedure') . ':', 0, 'L', false, 0);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->MultiCell(120, 0, $motion->getFormattedProposalStatus(), 0, 'L');
            $pdf->Ln(5);
        }

        $pdf->SetX(12);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->MultiCell(12, 0, '', 0, 'L', false, 0);

        $pdf->MultiCell(50, 0, \Yii::t('export', 'title') . ':', 0, 'L', false, 0);
        $pdf->SetFont('helvetica', 'B', 12);
        $borderStyle = ['width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [150, 150, 150]];
        $pdf->MultiCell(
            100,
            0,
            $motion->title,
            ['B' => $borderStyle],
            'L'
        );

        $pdf->Ln(9);
    }

    public function printAmendmentHeader(Amendment $amendment): void
    {
        $pdf      = $this->pdf;
        $settings = $this->motionType->getConsultation()->getSettings();

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->startPageGroup();
        $pdf->AddPage();

        $pdf->setDestination('amendment' . $amendment->id, 0, '');
        $pdf->Bookmark($amendment->getTitleWithPrefix(), 0, 0, '', 'BI', [128,0,0], -1, '#amendment' . $amendment->id);

        try {
            if (file_exists($settings->logoUrl)) {
                $pdf->setJPEGQuality(100);
                $pdf->Image($settings->logoUrl, 22, 32, 47, 26);
            }
        } catch (\Throwable $e) {
            // Catches an \ErrorException thrown with open_basedir restrictions. See https://github.com/CatoTH/antragsgruen/issues/811
        }

        if (!$settings->hideTitlePrefix) {
            $revName = $amendment->getFormattedTitlePrefix();
            if ($revName === '') {
                $revName = \Yii::t('export', 'draft');
                $pdf->SetFont('helvetica', 'I', 25);
                $width = (float)$pdf->GetStringWidth($revName, 'helvetica', 'I', 25) + 3.1;
            } else {
                $pdf->SetFont('helvetica', 'B', 25);
                $width = (float)$pdf->GetStringWidth($revName, 'helvetica', 'B', 25) + 3.1;
            }
            if ($width < 35) {
                $width = 35;
            }

            $pdf->SetXY(192 - $width, 37, true);
            $borderStyle = ['width' => 3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [150, 150, 150]];
            $this->pdf->MultiCell(
                $width,
                21,
                $revName,
                ['LTRB' => $borderStyle],
                'C',
                false,
                1,
                null,
                null,
                true,
                0,
                false,
                true,
                21, // defaults
                'M'
            );
        }

        $str = $amendment->getMyMotion()->motionType->titleSingular;
        $pdf->SetFont('helvetica', 'B', 25);
        $width = (float)$pdf->GetStringWidth($str);

        $pdf->SetXY((210 - $width) / 2, 60);
        $pdf->Write(20, $str);
        $pdf->SetLineStyle(
            [
                'width' => 3,
                'color' => array(150, 150, 150),
            ]
        );
        $pdf->Line((210 - $width) / 2, 78, (210 + $width) / 2, 78);

        $pdf->SetY(90);
        $intro = $this->motionType->getSettingsObj()->pdfIntroduction;
        if ($intro === \Yii::t('export', 'introduction')) {  // The default setting is just a placeholder
            $intro = null;
        }
        if ($intro) {
            $pdf->SetX(24);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->MultiCell(160, 13, $intro, 0, 'C');
            $pdf->Ln(7);
        }


        $pdf->SetX(12);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->MultiCell(12, 0, '', 0, 'L', false, 0);
        $pdf->MultiCell(50, 0, \Yii::t('export', 'Initiators') . ':', 0, 'L', false, 0);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(120, 0, $amendment->getInitiatorsStr(), 0, 'L');
        $pdf->Ln(5);

        if ($amendment->getMyMotionType()->getSettingsObj()->showProposalsInExports && $amendment->proposalStatus !== null && $amendment->isProposalPublic()) {
            $pdf->SetX(12);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->MultiCell(12, 0, '', 0, 'L', false, 0);
            $pdf->MultiCell(50, 0, \Yii::t('export', 'proposed_procedure') . ':', 0, 'L', false, 0);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->MultiCell(120, 0, $amendment->getFormattedProposalStatus(), 0, 'L');
            $pdf->Ln(5);
        }

        $pdf->SetX(12);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->MultiCell(12, 0, '', 0, 'L', false, 0);

        $pdf->MultiCell(50, 0, \Yii::t('export', 'title') . ':', 0, 'L', false, 0);
        $pdf->SetFont('helvetica', 'B', 12);
        $borderStyle = ['width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [150, 150, 150]];
        $pdf->MultiCell(
            100,
            0,
            $amendment->getTitle(),
            ['B' => $borderStyle],
            'L'
        );

        $pdf->Ln(9);
    }

    public function createPDFClass(): IPdfWriter
    {
        $pdf = new ByLDKPDF($this);

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setCellHeightRatio(1.5);

        $pdf->SetMargins(25, 40, 25);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM - 5);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('dejavusans', '', 10);

        $pdf->setHtmlVSpace([
            'ul'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'ol'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'li'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'div'        => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'p'          => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'blockquote' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
        ]);

        $this->pdf = $pdf;

        return $pdf;
    }
}
