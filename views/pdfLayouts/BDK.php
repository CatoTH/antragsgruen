<?php

namespace app\views\pdfLayouts;

use app\models\db\Amendment;
use app\models\db\Motion;
use setasign\Fpdi\Tcpdf\Fpdi;
use yii\helpers\Html;

class BDK extends IPDFLayout
{
    /** @var BDKPDF $pdf */
    protected $pdf;

    /**
     * @return Fpdi
     */
    public function createPDFClass()
    {
        $pdf = new BDKPDF();

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetMargins(25, 27, 25);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM - 5);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('dejavusans', '', 10);

        $pdf->setHtmlVSpace([
            'ul'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'li'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'div'        => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'p'          => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'blockquote' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
        ]);

        $this->pdf = $pdf;

        return $pdf;
    }

    /**
     * @param BDKPDF $pdf
     * @param string $pdfIntroduction
     * @param string $tableContent
     */
    public static function printHeaderTable(BDKPDF $pdf, $pdfIntroduction, $tableContent)
    {
        $title = str_replace("\n", '<br>', $pdfIntroduction);
        $pdf->SetY(35);
        $pdf->SetFont("helvetica", "", 13);
        $pdf->writeHTMLCell(185, 0, 10, 10, $title, 0, 1, 0, true, 'R');


        $pdf->SetFont("helvetica", "", 12);

        $pdf->setCellPaddings(2, 4, 2, 4);
        $pdf->writeHTMLCell(170, 0, 25, 35, $tableContent, 1, 1, 0, true, 'L');

        $pdf->Ln(7);
        $pdf->setCellPaddings(0, 0, 0, 0);
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

        $title = $motion->getMyMotionType()->getSettingsObj()->pdfIntroduction;

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

        BDK::printHeaderTable($this->pdf, $title, $motionData);
    }

    /**
     * @param Amendment $amendment
     */
    public function printAmendmentHeader(Amendment $amendment)
    {
        $pdf = $this->pdf;

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
