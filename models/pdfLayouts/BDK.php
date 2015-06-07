<?php

namespace app\models\pdfLayouts;

use app\models\db\Motion;

class BDK extends IPDFLayout
{
    /**
     * @param Motion $motion
     */
    public function printMotionHeader(Motion $motion)
    {
        $pdf      = $this->pdf;
        $settings = $this->motionType->consultation->getSettings();

        $pdf->writeHTML("Test");

        $pdf->Ln(9);
    }
}
