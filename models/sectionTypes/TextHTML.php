<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\components\latex\Content;
use app\components\opendocument\Text;
use app\models\db\AmendmentSection;
use app\models\exceptions\FormError;
use app\views\pdfLayouts\IPDFLayout;
use yii\helpers\Html;

class TextHTML extends ISectionType
{

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        return $this->getTextMotionFormField(true, $this->section->consultationSetting->fixedWidth);
    }

    /**
     * @return string
     */
    public function getAmendmentFormField()
    {
        $this->section->consultationSetting->maxLen = 0; // @TODO Dirty Hack
        $fixedWidth                                 = $this->section->consultationSetting->fixedWidth;
        return $this->getTextAmendmentFormField(true, $this->section->dataRaw, $fixedWidth);
    }

    /**
     * @param string $data
     * @throws FormError
     */
    public function setMotionData($data)
    {
        $this->section->dataRaw = $data;
        $this->section->data    = HTMLTools::cleanUntrustedHtml($data);
    }


    /**
     * @param string $data
     * @throws FormError
     */
    public function setAmendmentData($data)
    {
        /** @var AmendmentSection $section */
        $section          = $this->section;
        $section->data    = HTMLTools::cleanUntrustedHtml($data['consolidated']);
        $section->dataRaw = $data['raw'];
    }

    /**
     * @param bool $isRight
     * @return string
     */
    public function getSimple($isRight)
    {
        return $this->section->data;
    }

    /**
     * @return string
     */
    public function getAmendmentFormatted()
    {
        return ''; // @TODO
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->section->data == '');
    }


    /**
     * @param IPDFLayout $pdfLayout
     * @param \TCPDF $pdf
     */
    public function printMotionToPDF(IPDFLayout $pdfLayout, \TCPDF $pdf)
    {
        if ($this->isEmpty()) {
            return;
        }

        if (!$pdfLayout->isSkippingSectionTitles($this->section)) {
            $pdfLayout->printSectionHeading($this->section->consultationSetting->title);
        }

        $html = $this->section->data;
        // Some umlaut characters with unusual UTF-8-encoding (0x61CC88 for "ü")
        // are not shown correctly in PDF => convert them to the normal encoding
        if (function_exists('normalizer_normalize')) {
            $html = normalizer_normalize($html);
        }
        $pdf->writeHTML($html);
    }

    /**
     * @param IPDFLayout $pdfLayout
     * @param \TCPDF $pdf
     */
    public function printAmendmentToPDF(IPDFLayout $pdfLayout, \TCPDF $pdf)
    {
        $this->printMotionToPDF($pdfLayout, $pdf);
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
     * @param bool $isRight
     * @param Content $content
     */
    public function printMotionTeX($isRight, Content $content)
    {
        if ($isRight) {
            $content->textRight .= '[TEST HTML]'; // @TODO
        } else {
            $content->textMain .= '[TEST HTML]'; // @TODO
        }
    }

    /**
     * @param bool $isRight
     * @param Content $content
     */
    public function printAmendmentTeX($isRight, Content $content)
    {
        if ($isRight) {
            $content->textRight .= '[TEST HTML]'; // @TODO
        } else {
            $content->textMain .= '[TEST HTML]'; // @TODO
        }
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
