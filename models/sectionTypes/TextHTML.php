<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\components\latex\Content;
use app\models\db\AmendmentSection;
use app\models\db\Consultation;
use app\views\pdfLayouts\IPDFLayout;
use setasign\Fpdi\TcpdfFpdi;
use yii\helpers\Html;
use \CatoTH\HTML2OpenDocument\Text as ODTText;

class TextHTML extends Text
{

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        return $this->getTextMotionFormField(true, $this->section->getSettings()->fixedWidth);
    }

    /**
     * @return string
     */
    public function getAmendmentFormField()
    {
        $this->section->getSettings()->maxLen = 0; // @TODO Dirty Hack
        $fixedWidth                           = $this->section->getSettings()->fixedWidth;

        $pre = ($this->section->dataRaw ? $this->section->dataRaw : $this->section->data);
        return $this->getTextAmendmentFormField(true, $pre, $fixedWidth);
    }

    /**
     * @param string $data
     */
    public function setMotionData($data)
    {
        $this->section->dataRaw = $data;
        $this->section->data    = HTMLTools::correctHtmlErrors($data);
    }


    /**
     * @param array $data
     */
    public function setAmendmentData($data)
    {
        /** @var AmendmentSection $section */
        $section          = $this->section;
        $section->data    = HTMLTools::correctHtmlErrors($data['consolidated']);
        $section->dataRaw = $data['raw'];
    }

    /**
     * @param bool $isRight
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSimple($isRight)
    {
        return $this->section->data;
    }

    /**
     * @param string $sectionTitlePrefix
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAmendmentFormatted($sectionTitlePrefix = '')
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
     * @param TcpdfFpdi $pdf
     */
    public function printMotionToPDF(IPDFLayout $pdfLayout, TcpdfFpdi $pdf)
    {
        if ($this->isEmpty()) {
            return;
        }

        if (!$pdfLayout->isSkippingSectionTitles($this->section)) {
            $pdfLayout->printSectionHeading($this->section->getSettings()->title);
        }

        $html = $this->section->data;
        // instead of <span class="strike"></span> TCPDF can only handle <s></s>
        // for striking through text
        $pattern = '/<span class="strike">(.*)<\/span>/iUs';
        $replace = '<s>${1}</s>';
        $html    = preg_replace($pattern, $replace, $html);
        // Some umlaut characters with unusual UTF-8-encoding (0x61CC88 for "ü")
        // are not shown correctly in PDF => convert them to the normal encoding
        if (function_exists('normalizer_normalize')) {
            $html = normalizer_normalize($html);
        }
        $pdf->writeHTML($html);
    }

    /**
     * @param IPDFLayout $pdfLayout
     * @param TcpdfFpdi $pdf
     */
    public function printAmendmentToPDF(IPDFLayout $pdfLayout, TcpdfFpdi $pdf)
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
     * @param Consultation $consultation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function printMotionTeX($isRight, Content $content, Consultation $consultation)
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
     * @param ODTText $odt
     */
    public function printMotionToODT(ODTText $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[Kann nicht angezeigt werden]', false); // @TODO
    }

    /**
     * @param ODTText $odt
     */
    public function printAmendmentToODT(ODTText $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
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
