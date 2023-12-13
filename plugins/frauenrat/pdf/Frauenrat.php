<?php

namespace app\plugins\frauenrat\pdf;

use app\models\db\{Amendment, Consultation, ConsultationMotionType, Motion, MotionSupporter};
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};

class Frauenrat extends IPDFLayout
{
    private function printHeader(FrauenratPdf $pdf, Consultation $consultation, ConsultationMotionType $motionType, string $title1, ?string $title2): void
    {
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->startPageGroup();
        $pdf->AddPage();

        $left     = 23.5;
        $abs      = 5;

        $this->setHeaderLogo($consultation, $abs, 20, null);
        if ($this->headerlogo) {
            $logo = $this->headerlogo;
            $pdf->setJPEGQuality(100);
            $pdf->Image('@' . $logo['data'], $logo['x'], $logo['y'], $logo['w'], $logo['h']);
            $pdf->setY($logo['y'] + $abs);
        }

        //$pdf->SetFont($pdf->calibriBold, 'B', $fontsize);
        //$pdf->SetTextColor(86, 55, 12, 59);
        //$pdf->Write(0, mb_strtoupper($motion->getMyMotionType()->titleSingular, 'UTF-8') . "\n");

        $pdf->SetTextColor(100, 100, 100, 100);
        $wraptop = $pdf->getY() + $abs;
        $pdf->SetXY($left, $wraptop);

        $pdf->SetFont($pdf->calibri, '', 11);
        $intro = $motionType->getSettingsObj()->pdfIntroduction;
        if ($intro) {
            $pdf->MultiCell(160, 0, $intro, 0, 'L');
            $pdf->Ln(3);
        }

        $pdf->SetX($left);
        $pdf->SetTextColor(86, 55, 12, 59);
        $pdf->SetFont($pdf->calibriBold, 'B', 16);
        $pdf->MultiCell(130, 0, mb_strtoupper($title1, 'UTF-8'), 0, 'L');

        $pdf->Ln(3);

        if ($title2) {
            $pdf->SetX($left);
            $pdf->SetTextColor(100, 0, 0, 30);
            $pdf->SetFont($pdf->calibriBold, 'B', 12);
            $pdf->MultiCell(130, 0, $title2, 0, 'L');
            $pdf->Ln(3);
        }

        $pdf->SetTextColor(100, 100, 100, 100);
        $pdf->SetFont($pdf->calibri, '', 11);
    }
    public function printMotionHeader(Motion $motion): void
    {
        /** @var FrauenratPdf $pdf */
        $pdf = $this->pdf;
        $left     = 23.5;

        $this->printHeader($pdf, $motion->getMyConsultation(), $motion->getMyMotionType(), $motion->getTitleWithPrefix(), null);

        $addressedTo = null;
        $initiatorNames = [];
        $contact = $topic = [];
        foreach ($motion->getInitiators() as $initiator) {
            if ($initiator->personType === MotionSupporter::PERSON_ORGANIZATION) {
                $initiatorNames[] = $initiator->organization;
            } else {
                $initiatorNames[] = $initiator->name ?: $initiator->organization;
            }
            if ($initiator->contactName) {
                $contact[] = 'Name: ' . $initiator->contactName;
            }
            if ($initiator->contactEmail) {
                $contact[] = 'E-Mail: ' . $initiator->contactEmail;
            }
            if ($initiator->contactPhone) {
                $contact[] = 'Telefon: ' . $initiator->contactPhone;
            }
        }
        foreach ($motion->getPublicTopicTags() as $tag) {
            $topic[] = $tag->title;
        }
        foreach ($motion->getActiveSections() as $section) {
            if (str_contains($section->getSettings()->title, 'Adressat')  ) {
                $addressedTo = $section->getData();
            }
        }

        $initiatorTitle = (count($initiatorNames) > 1 ? \Yii::t('motion', 'initiators_x') : \Yii::t('motion', 'initiators_1'));
        $data = [
            $initiatorTitle      => implode(", ", $initiatorNames),
            'Ansprechpartner*in' => implode("\n", $contact),
        ];
        if (count($topic) > 0) {
            $data['Themenbereich'] = implode(", ", $topic);
        }
        if ($addressedTo) {
            $data['Adressat*in'] = $addressedTo;
        }


        foreach ($data as $key => $val) {
            $pdf->SetX($left);
            $pdf->MultiCell(42, 0, $key . ':', 0, 'L', false, 0);
            $pdf->MultiCell(100, 0, $val, 0, 'L');
            $pdf->Ln(2);
        }

        $pdf->Ln(9);

        $pdf->SetX($left);

        $pdf->SetTextColor(100, 100, 100, 100);
        $pdf->SetFont($pdf->calibri, '', 12);
    }

    public function printAmendmentHeader(Amendment $amendment): void
    {
        /** @var FrauenratPdf $pdf */
        $pdf = $this->pdf;
        $left           = 23.5;

        $title1 = $amendment->getMyMotion()->getTitleWithPrefix();
        $title2 = \Yii::t('amend', 'amendment') . ' ' . $amendment->getFormattedTitlePrefix();
        $this->printHeader($pdf, $amendment->getMyConsultation(), $amendment->getMyMotionType(), $title1, $title2);

        $initiatorName = null;
        $contact = [];
        foreach ($amendment->getInitiators() as $initiator) {
            $initiatorName = $initiator->organization;
            if ($initiator->contactName) {
                $contact[] = 'Name: ' . $initiator->contactName;
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

    public function printSectionHeading(string $text): void
    {
        if (str_contains($_SERVER['REQUEST_URI'], 'TOP_5')  ) {
            return;
        }

        /** @var FrauenratPdf $pdf */
        $pdf = $this->pdf;

        $pdf->SetFont($pdf->calibriBold, 'b', 12);
        $pdf->SetTextColor(46, 116, 181);
        $pdf->ln(4);
        $pdf->MultiCell(0, 0, '<h4>' . $text . '</h4>', 0, 'L', false, 1, 22, null, true, 0, true);
        $pdf->SetFont($pdf->calibri, '', 12);
        $pdf->SetTextColor(0, 0, 0);
    }
}
