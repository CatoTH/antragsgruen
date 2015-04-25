<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\models\db\AmendmentSection;
use app\models\exceptions\FormError;

class TextHTML extends ISectionType
{

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        return $this->getTextMotionFormField(true);
    }

    /**
     * @return string
     */
    public function getAmendmentFormField()
    {
        return $this->getTextAmendmentFormField(true);
    }

    /**
     * @param string $data
     * @throws FormError
     */
    public function setMotionData($data)
    {
        $this->section->data = HTMLTools::cleanUntrustedHtml($data);
    }



    /**
     * @param string $data
     * @throws FormError
     */
    public function setAmendmentData($data)
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        $section->data = HTMLTools::cleanUntrustedHtml($data['consolidated']);
        $section->dataRaw = $data['raw'];
    }

    /**
     * @return string
     */
    public function showSimple()
    {
        return $this->section->data;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->section->data == '');
    }


    /**
     * @param \TCPDF $pdf
     */
    public function printToPDF(\TCPDF $pdf)
    {
        $pdf->SetFont("helvetica", "", 12);
        $pdf->writeHTML("<h3>" . $this->section->consultationSetting->title . "</h3>");

        $html = $this->section->data;
        // Some umlaut characters with unusual UTF-8-encoding (0x61CC88 for "ü")
        // are not shown correctly in PDF => convert them to the normal encoding
        if (function_exists("normalizer_normalize")) {
            $html = normalizer_normalize($html);
        }
        $pdf->writeHTML($html);
    }
}
