<?php

namespace app\models\sectionTypes;

use app\components\html2pdf\Content as HtmlToPdfContent;
use app\components\HTMLTools;
use app\components\latex\Content as LatexContent;
use app\models\db\{AmendmentSection, Consultation};
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use yii\helpers\Html;
use \CatoTH\HTML2OpenDocument\Text as ODTText;

class TextHTML extends Text
{
    public function getMotionFormField(): string
    {
        return $this->getTextMotionFormField(true, !!$this->section->getSettings()->fixedWidth);
    }

    public function getAmendmentFormField(): string
    {
        $this->section->getSettings()->maxLen = 0; // @TODO Dirty Hack
        $fixedWidth                           = !!$this->section->getSettings()->fixedWidth;

        $pre = ($this->section->dataRaw ? $this->section->dataRaw : $this->section->getData());
        return $this->getTextAmendmentFormField(true, $pre, $fixedWidth);
    }

    /**
     * @param string $data
     */
    public function setMotionData($data): void
    {
        $this->section->dataRaw = $data;
        $this->section->setData(HTMLTools::correctHtmlErrors($data));
    }

    public function deleteMotionData(): void
    {
        $this->section->setData('');
        $this->section->dataRaw = '';
    }

    /**
     * @param array $data
     */
    public function setAmendmentData($data): void
    {
        /** @var AmendmentSection $section */
        $section          = $this->section;
        $section->data    = HTMLTools::correctHtmlErrors($data['consolidated']);
        $section->dataRaw = $data['raw'];
    }

    public function getSimple(bool $isRight, bool $showAlways = false): string
    {
        return $this->section->getData();
    }

    public function getAmendmentFormatted(string $htmlIdPrefix = ''): string
    {
        return ''; // @TODO
    }

    public function isEmpty(): bool
    {
        return ($this->section->getData() === '');
    }

    public function showIfEmpty(): bool
    {
        return false;
    }

    public function printMotionToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        if ($this->isEmpty()) {
            return;
        }

        if ($this->section->getSettings()->printTitle) {
            $pdfLayout->printSectionHeading($this->getTitle());
        }

        $html = $this->section->getData();
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
        $pdf->writeHTML((string)$html);
    }

    public function printAmendmentToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        $this->printMotionToPDF($pdfLayout, $pdf);
    }

    public function getMotionPlainText(): string
    {
        return HTMLTools::toPlainText($this->section->getData());
    }

    public function getAmendmentPlainText(): string
    {
        return HTMLTools::toPlainText($this->section->getData());
    }

    public function printMotionTeX(bool $isRight, LatexContent $content, Consultation $consultation): void
    {
        if ($isRight) {
            $content->textRight .= '[TEST HTML]'; // @TODO
        } else {
            $content->textMain .= '[TEST HTML]'; // @TODO
        }
    }

    public function printAmendmentTeX(bool $isRight, LatexContent $content): void
    {
        if ($isRight) {
            $content->textRight .= '[TEST HTML]'; // @TODO
        } else {
            $content->textMain .= '[TEST HTML]'; // @TODO
        }
    }

    public function printMotionHtml2Pdf(bool $isRight, HtmlToPdfContent $content, Consultation $consultation): void
    {
        if ($isRight) {
            $content->textRight .= $this->section->getData();
        } else {
            $content->textMain .= $this->section->getData();
        }
    }

    public function printAmendmentHtml2Pdf(bool $isRight, HtmlToPdfContent $content): void
    {
        if ($isRight) {
            $content->textRight .= $this->section->getData();
        } else {
            $content->textMain .= $this->section->getData();
        }
    }

    public function getMotionODS(): string
    {
        return '<p>Full HTML is not convertible to Spreadsheets</p>';
    }

    public function getAmendmentODS(): string
    {
        return '<p>Full HTML is not convertible to Spreadsheets</p>';
    }

    public function printMotionToODT(ODTText $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);
        $odt->addHtmlTextBlock('[Full HTML is not convertible to ODT]', false); // @TODO
    }

    public function printAmendmentToODT(ODTText $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);
        $odt->addHtmlTextBlock('[Full HTML is not convertible to ODT]', false); // @TODO
    }

    public function matchesFulltextSearch(string $text): bool
    {
        $data = strip_tags($this->section->getData());
        return (mb_stripos($data, $text) !== false);
    }
}
