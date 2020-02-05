<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\components\latex\Content;
use app\models\db\{AmendmentSection, Consultation};
use app\views\pdfLayouts\IPDFLayout;
use setasign\Fpdi\Tcpdf\Fpdi;
use yii\helpers\Html;
use \CatoTH\HTML2OpenDocument\Text as ODTText;

class TextHTML extends Text
{
    public function getMotionFormField(): string
    {
        return $this->getTextMotionFormField(true, $this->section->getSettings()->fixedWidth);
    }

    public function getAmendmentFormField(): string
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

    public function deleteMotionData()
    {
        $this->section->data    = '';
        $this->section->dataRaw = '';
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

    public function getSimple(bool $isRight, bool $showAlways = false): string
    {
        return $this->section->data;
    }

    public function getAmendmentFormatted(string $sectionTitlePrefix = ''): string
    {
        return ''; // @TODO
    }

    public function isEmpty(): bool
    {
        return ($this->section->data == '');
    }

    public function printMotionToPDF(IPDFLayout $pdfLayout, Fpdi $pdf): void
    {
        if ($this->isEmpty()) {
            return;
        }

        if ($this->section->getSettings()->printTitle) {
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

    public function printAmendmentToPDF(IPDFLayout $pdfLayout, Fpdi $pdf): void
    {
        $this->printMotionToPDF($pdfLayout, $pdf);
    }

    public function getMotionPlainText(): string
    {
        return HTMLTools::toPlainText($this->section->data);
    }

    public function getAmendmentPlainText(): string
    {
        return HTMLTools::toPlainText($this->section->data);
    }

    public function printMotionTeX(bool $isRight, Content $content, Consultation $consultation): void
    {
        if ($isRight) {
            $content->textRight .= '[TEST HTML]'; // @TODO
        } else {
            $content->textMain .= '[TEST HTML]'; // @TODO
        }
    }

    public function printAmendmentTeX(bool $isRight, Content $content): void
    {
        if ($isRight) {
            $content->textRight .= '[TEST HTML]'; // @TODO
        } else {
            $content->textMain .= '[TEST HTML]'; // @TODO
        }
    }

    public function getMotionODS(): string
    {
        return '<p>Full HTML is not convertable to Spreadsheets</p>';
    }

    public function getAmendmentODS(): string
    {
        return '<p>Full HTML is not convertable to Spreadsheets</p>';
    }

    public function printMotionToODT(ODTText $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[Full HTML is not convertable to ODT]', false); // @TODO
    }

    public function printAmendmentToODT(ODTText $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[Full HTML is not convertable to ODT]', false); // @TODO
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
