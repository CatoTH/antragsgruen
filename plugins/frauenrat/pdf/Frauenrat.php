<?php

namespace app\plugins\frauenrat\pdf;

use app\models\db\{Amendment, Motion};
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use yii\helpers\Html;

class Frauenrat extends IPDFLayout
{
    public function printMotionHeader(Motion $motion): void
    {
        /** @var FrauenratPdf $pdf */
        $pdf = $this->pdf;

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->startPageGroup();
        $pdf->AddPage();

        $left     = 23.5;
        $abs      = 5;
        $fontsize = 30;

        $this->setHeaderLogo($motion->getMyConsultation(), $abs, 30, null);
        if ($this->headerlogo) {
            $logo = $this->headerlogo;
            $pdf->setJPEGQuality(100);
            $pdf->Image('@' . $logo['data'], $logo['x'], $logo['y'], $logo['w'], $logo['h']);
            $pdf->setY($logo['y'] + $abs);
        }

        $pdf->SetFont($pdf->calibriBold, 'B', $fontsize);
        $pdf->SetTextColor(40, 40, 40, 40);
        //$pdf->SetXY($left, $wraptop);
        $pdf->Write(0, mb_strtoupper($motion->motionType->titleSingular, 'UTF-8') . "\n");

        $pdf->SetTextColor(100, 100, 100, 100);
        $wraptop = $pdf->getY() + $abs;
        $pdf->SetXY($left, $wraptop);

        $pdf->SetFont($pdf->calibri, '', 11);
        $intro = $motion->getMyMotionType()->getSettingsObj()->pdfIntroduction;
        if ($intro) {
            $pdf->MultiCell(160, 0, $intro, 0, 'L');
            $pdf->Ln(3);
        }

        $initiatorName = $addressedTo = null;
        $contact = $topic = [];
        foreach ($motion->getInitiators() as $initiator) {
            $initiatorName = $initiator->organization;
            if ($initiator->contactName) {
                $contact[] = 'Ansprechpartner*in: ' . $initiator->contactName;
            }
            if ($initiator->contactEmail) {
                $contact[] = 'E-Mail: ' . $initiator->contactEmail;
            }
            if ($initiator->contactPhone) {
                $contact[] = 'Telefon: ' . $initiator->contactPhone;
            }
        }
        foreach ($motion->tags as $tag) {
            $topic[] = $tag->title;
        }
        foreach ($motion->getActiveSections() as $section) {
            if (strpos($section->getSettings()->title, 'Adressat') !== false) {
                $addressedTo = $section->getData();
            }
        }

        $data = [
            'Antragsteller*in'   => $initiatorName,
            'Ansprechpartner*in' => implode("\n", $contact),
            'Themenbereich'      => implode(", ", $topic),
            'Adressat*in'        => $addressedTo,
        ];


        foreach ($data as $key => $val) {
            $pdf->SetX($left);
            $pdf->MultiCell(42, 0, $key . ':', 0, 'L', false, 0);
            $pdf->MultiCell(120, 0, $val, 0, 'L');
            $pdf->Ln(2);
        }

        $pdf->Ln(9);

        $pdf->SetX($left);
        $pdf->SetFont($pdf->calibriBold, 'B', 12);
        $pdf->writeHTML('<h3> &nbsp;' . Html::encode(mb_strtoupper($motion->getTitleWithPrefix(), 'UTF-8')) . '</h3>');

        $pdf->SetFont($pdf->calibri, '', 12);
    }

    public function printAmendmentHeader(Amendment $amendment): void
    {
        /** @var FrauenratPdf $pdf */
        $pdf = $this->pdf;

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->startPageGroup();
        $pdf->AddPage();

        $left           = 23.5;
        $abs            = 5;
        $title1Fontsize = 15;
        $title2Fontsize = 25;

        $this->setHeaderLogo($amendment->getMyConsultation(), $abs, 30, null);
        if ($this->headerlogo) {
            $logo = $this->headerlogo;
            $pdf->setJPEGQuality(100);
            $pdf->Image('@' . $logo['data'], $logo['x'], $logo['y'], $logo['w'], $logo['h']);
            $pdf->setY($logo['y'] + $abs);
        }

        $pdf->SetTextColor(100, 100, 100, 100);
        $pdf->SetFont($pdf->calibriItalic, 'I', 11);
        $pdf->SetXY($left, $pdf->getY());
        $pdf->Ln(3);
        $intro = $amendment->getMyMotionType()->getSettingsObj()->pdfIntroduction;
        if ($intro) {
            $pdf->MultiCell(0, 0, trim($intro), 0, 'L');
            $pdf->Ln(3);
        }

        $pdf->SetTextColor(40, 40, 40, 40);
        $pdf->SetFont($pdf->calibriBold, 'B', $title1Fontsize);
        $pdf->MultiCell(0, 0, trim($amendment->getMyMotion()->getTitleWithPrefix()), 0, 'L');
        $pdf->Ln(3);
        $pdf->SetFont($pdf->calibriBold, 'B', $title2Fontsize);
        $pdf->Write(0, mb_strtoupper(\Yii::t('amend', 'amendment') . ' ' . $amendment->titlePrefix, 'UTF-8') . "\n");
        $pdf->Ln(3);

        $pdf->SetX($left);
        $pdf->SetTextColor(100, 100, 100, 100);
        $pdf->SetFont($pdf->calibri, '', 11);

        $initiatorName = null;
        $contact = [];
        foreach ($amendment->getInitiators() as $initiator) {
            $initiatorName = $initiator->organization;
            if ($initiator->contactName) {
                $contact[] = 'Ansprechpartner*in: ' . $initiator->contactName;
            }
            if ($initiator->contactEmail) {
                $contact[] = 'E-Mail: ' . $initiator->contactEmail;
            }
            if ($initiator->contactPhone) {
                $contact[] = 'Telefon: ' . $initiator->contactPhone;
            }
        }

        $data = [
            'Antragsteller*in'   => $initiatorName,
            'Ansprechpartner*in' => implode("\n", $contact),
        ];


        foreach ($data as $key => $val) {
            $pdf->SetX($left);
            $pdf->MultiCell(42, 0, $key . ':', 0, 'L', false, 0);
            $pdf->MultiCell(120, 0, $val, 0, 'L');
            $pdf->Ln(2);
        }

        $pdf->SetFont($pdf->calibri, '', 12);
        $pdf->ln(7);
    }

    public function createPDFClass(): IPdfWriter
    {
        $pdf = new FrauenratPdf($this);

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setCellHeightRatio(1.5);

        $pdf->SetMargins(23, 23, 23);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM - 5);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont($pdf->calibri, '', 10);

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

    public function printSectionHeading(string $text)
    {
        /** @var FrauenratPdf $pdf */
        $pdf = $this->pdf;

        $pdf->SetFont($pdf->calibriBold, 'b', 12);
        $pdf->ln(2);
        $pdf->MultiCell(0, 0, '<h4>' . $text . '</h4>', 0, 'L', false, 1, '', '', true, 0, true);
        $pdf->SetFont($pdf->calibri, '', 12);
    }
}
