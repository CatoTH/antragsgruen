<?php

namespace app\views\pdfLayouts;

use app\models\db\Amendment;
use app\models\db\IMotionSection;
use app\models\db\Motion;
use yii\helpers\Html;

class BDK extends IPDFLayout
{
    /** @var BDKPDF $pdf */
    protected $pdf;

    /**
     * @return \FPDI
     */
    public function createPDFClass()
    {
        $pdf = new BDKPDF($this);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetMargins(25, 27, 25);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM - 5);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('dejavusans', '', 10);

        $this->pdf = $pdf;

        return $pdf;
    }

    /**
     * @param Motion $motion
     */
    public function printMotionHeader(Motion $motion)
    {
        $pdf = $this->pdf;

        $pdf->setMotionTitle($motion->titlePrefix, $motion->title);
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);


        $title = $motion->getMyConsultation()->getSettings()->pdfIntroduction;
        $pdf->SetY(40);
        $pdf->SetFont("helvetica", "B", 13);
        $pdf->writeHTMLCell(185, 0, 10, 10, $title, 0, 1, 0, true, 'R');


        $pdf->SetFont("helvetica", "", 12);
        $motionData = '<span style="font-size: 20px; font-weight: bold">';
        $motionData .= Html::encode($motion->titlePrefix) . ' </span>';
        $motionData .= '<span style="font-size: 16px; font-weight: bold;">';
        $motionData .= Html::encode($motion->title) . '</span>';
        $motionData .= '<br><br>';

        $motionData .= '<table>';
        foreach ($motion->getDataTable() as $key => $val) {
            $motionData .= '<tr><th style="width: 28%;">' . Html::encode($key) . ':</th>';
            $motionData .= '<td>' . Html::encode($val) . '</td></tr>';
        }

        $motionData .= '</table>';

        $pdf->writeHTMLCell(170, 0, 25, 35, $motionData, 1, 1, 0, true, 'L');

        $pdf->Ln(11);
    }

    /**
     * @param Amendment $amendment
     */
    public function printAmendmentHeader(Amendment $amendment)
    {
        $pdf = $this->pdf;

        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);


        $title = $amendment->getMyConsultation()->getSettings()->pdfIntroduction;
        $pdf->SetY(40);
        $pdf->SetFont("helvetica", "B", 13);
        $pdf->writeHTMLCell(185, 0, 10, 10, $title, 0, 1, 0, true, 'R');


        $pdf->SetFont("helvetica", "", 12);
        $amendmentData = '<span style="font-size: 16px; font-weight: bold;">';
        $amendmentData .= Html::encode($amendment->getTitle()) . '</span>';
        $amendmentData .= '<br><br>';

        $amendmentData .= '<table>';
        $amendmentData .= '<tr><th style="width: 28%;">Antragsteller*innen:</th>';
        $amendmentData .= '<td>' . Html::encode($amendment->getInitiatorsStr()) . '</td></tr>';

        $amendmentData .= '</table>';

        $pdf->writeHTMLCell(170, 0, 25, 35, $amendmentData, 1, 1, 0, true, 'L');

        $pdf->Ln(11);
    }

    /**
     * @param IMotionSection $section
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isSkippingSectionTitles(IMotionSection $section)
    {
        return false;
    }
}
