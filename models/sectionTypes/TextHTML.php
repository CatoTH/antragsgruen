<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\components\opendocument\Text;
use app\models\db\AmendmentSection;
use app\models\exceptions\FormError;
use yii\helpers\Html;

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
    public function getSimple()
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
    public function printMotionToPDF(\TCPDF $pdf)
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

    /**
     * @param \TCPDF $pdf
     */
    public function printAmendmentToPDF(\TCPDF $pdf)
    {
        $this->printMotionToPDF($pdf);
    }

    /**
     * @return string
     */
    public function getMotionPlainText()
    {
        return HTMLTools::toPlainText($this->section->data);
    }

    /**
     * @return string
     */
    public function getAmendmentPlainText()
    {
        return HTMLTools::toPlainText($this->section->data);
    }

    /**
     * @return string
     */
    public function getMotionTeX()
    {
        return 'Test'; //  @TODO
    }

    /**
     * @return string
     */
    public function getAmendmentTeX()
    {
        return 'Test'; //  @TODO
    }

    /**
     * @return string
     */
    public function getMotionODS()
    {
        return '<p>Kann nicht angezeigt werden</p>';
    }

    /**
     * @return string
     */
    public function getAmendmentODS()
    {
        return '<p>Kann nicht angezeigt werden</p>';
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printMotionToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->consultationSetting->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[Kann nicht angezeigt werden]', false); // @TODO
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printAmendmentToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->consultationSetting->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[Kann nicht angezeigt werden]', false); // @TODO
    }

    /**
     * @param $text
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function matchesFulltextSearch($text)
    {
        $data = strip_tags($this->section->data);
        return (mb_stripos($data, $text) !== false);
    }
}
