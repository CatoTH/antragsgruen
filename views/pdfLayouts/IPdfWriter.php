<?php

namespace app\views\pdfLayouts;

use app\models\db\MotionSection;
use Com\Tecnick\Pdf\Import\PageTemplateInterface;

class IPdfWriter extends \TCPDF
{
    /**
     * Replicates the private \TCPDF::engineNew(), wiring in the engine subclass that implements
     * Antragsgrün's custom list rendering (see IPdfWriterEngine).
     *
     * Note: \TCPDF::SetProtection() re-creates the engine through the private engineNew()
     * and would silently replace it with the default engine; it must not be used with this class.
     */
    protected function engineInit(string $unit): void
    {
        $unit = strtolower(trim($unit)) === '' ? 'mm' : strtolower(trim($unit));
        $this->docunit = $unit;
        $eng = new IPdfWriterEngine(
            $this->docunit,
            $this->unicode,
            false,
            true,
            $this->pdfamode,
            null,
            $this->fileOptions(),
        );
        $eng->pagecontexthook = $this->ambientPageContent(...);
        $this->eng = $eng;
        $this->kratio = $eng->toPoints(1.0);
    }

    /**
     * Registers a PDF given as a binary string as an import source (replaces FPDI's setSourceFile).
     *
     * @return string the source ID to be passed to the other import methods
     *
     * @throws \Com\Tecnick\Pdf\Import\ImportException
     */
    public function importPdfSource(string $data): string
    {
        return $this->engine()->setImportSourceData($data);
    }

    /**
     * @throws \Com\Tecnick\Pdf\Import\ImportException
     */
    public function getImportedPageCount(string $sourceId): int
    {
        return $this->engine()->getSourcePageCount($sourceId);
    }

    /**
     * @throws \Com\Tecnick\Pdf\Import\ImportException
     */
    public function importPdfPage(string $sourceId, int $pageNo): PageTemplateInterface
    {
        return $this->engine()->importPage($sourceId, $pageNo);
    }

    /**
     * Returns the size of an imported page in user units (mm).
     *
     * @return array{width: float, height: float, orientation: string}
     */
    public function getImportedPageSize(PageTemplateInterface $page): array
    {
        $width = $this->engine()->toUnit($page->getWidth());
        $height = $this->engine()->toUnit($page->getHeight());

        return [
            'width' => $width,
            'height' => $height,
            'orientation' => ($width > $height ? 'L' : 'P'),
        ];
    }

    /**
     * Places an imported page onto the current page (replaces FPDI's useTemplate).
     * Coordinates and sizes are given in user units (mm); if width/height are omitted,
     * the original size of the imported page is used.
     */
    public function useImportedPage(PageTemplateInterface $page, float $x = 0, float $y = 0, ?float $width = null, ?float $height = null): void
    {
        $this->engine()->useImportedPage($page, $x, $y, $width, $height);
    }

    /**
     * This adds <br>-tags where necessary.
     * Test cases are collected in the "Listen-Test"-motion.
     * Check in the TCPDF-generated PDF that line numbers match the lines.
     *
     * @param string[] $linesArr
     *
     * @return string[]
     */
    private function printMotionToPDFAddLinebreaks(array $linesArr): array
    {
        for ($i = 1; $i < count($linesArr); $i++) {
            // Does this line start with an ol/ul/li?
            if (!preg_match('/^<(ol|ul|li)/siu', $linesArr[$i])) {
                continue;
            }
            // Does the previous line end a block element? If not, we need the extra BR
            if (!preg_match('/<\/(div|p|blockquote|ul|ol|h1|h2|h3|h4|h5|h6)>$/siu', $linesArr[$i - 1])) {
                $linesArr[$i] = '<br>' . $linesArr[$i];
            }
        }

        return $linesArr;
    }

    public function getMotionFont(?MotionSection $section): string
    {
        if ($section && $section->getSettings()->fixedWidth) {
            return 'dejavusansmono';
        } else {
            return 'helvetica';
        }
    }

    public function getMotionFontSize(?MotionSection $section): int
    {
        if ($section) {
            $lineLength = $section->getConsultation()->getSettings()->lineLength;

            return ($lineLength > 70 ? 10 : 11);
        } else {
            return 10;
        }
    }

    public function printMotionSection(MotionSection $section): void
    {
        $linenr   = $section->getFirstLineNumber();
        $textSize = $this->getMotionFontSize($section);
        $fontName = $this->getMotionFont($section);

        $this->SetFont($fontName, '', $textSize);
        $this->Ln(7);

        $hasLineNumbers = !!$section->getSettings()->lineNumbers;
        if ($section->getSettings()->fixedWidth || $hasLineNumbers) {
            $paragraphs = $section->getTextParagraphObjects($hasLineNumbers);
            foreach ($paragraphs as $paragraph) {
                $linesArr = [];
                foreach ($paragraph->lines as $line) {
                    $line       = str_replace('###LINENUMBER###', '', $line);
                    $line       = preg_replace('/<br>\s*$/siu', '', $line);
                    $linesArr[] = $line . '';
                }

                // Hint about <li>s: The spacing between list items is created by </li><br><li>-markup.
                // This obviously is incorrect according to HTML, but is rendered correctly neverless.
                // We just have to take care about additional spacing for the line numbers in these cases.

                if ($hasLineNumbers) {
                    $lineNos = [];
                    for ($i = 0; $i < count($paragraph->lines); $i++) {
                        if (preg_match('/^<(ul|ol|li)/siu', $linesArr[$i])) {
                            $lineNos[] = ''; // Just for having an additional <br>
                        }
                        $lineNos[] = $linenr++;
                    }
                    $text2 = implode('<br>', $lineNos);
                } else {
                    $text2 = '';
                }

                $y = $this->getY();
                $this->SetFont($fontName, '', $textSize * 2 / 3);
                $this->SetTextColor(100, 100, 100);
                $this->setCellHeightRatio(2.23);
                $this->writeHTMLCell(12, 0, 12, $y, $text2, 0, 0, false, true, '', true);

                $this->SetFont($fontName, '', $textSize);
                $this->SetTextColor(0, 0, 0);
                $this->setCellHeightRatio(1.5);
                $linesArr = $this->printMotionToPDFAddLinebreaks($linesArr);
                $text1    = implode('<br>', $linesArr);
                $text1    = str_replace('</li><br><br><li', '</li><br><li', $text1);

                // instead of <span class="strike"></span> TCPDF can only handle <s></s>
                // for striking through text
                $text1 = preg_replace('/<span class="strike">(.*)<\/span>/iUs', '<s>${1}</s>', $text1);

                // instead of <span class="underline"></span> TCPDF can only handle <u></u>
                // for underlined text
                $text1 = preg_replace('/<span class="underline">(.*)<\/span>/iUs', '<u>${1}</u>', $text1);

                $this->writeHTMLCell(173, 0, 24, $y, $text1, 0, 1, false, true, '', true);

                $this->Ln(7);
            }
        } else {
            $paras = $section->getTextParagraphLines();
            foreach ($paras as $para) {
                $html = str_replace('###LINENUMBER###', '', implode('', $para->lines));
                $html = str_replace('</li>', '<br></li>', $html);
                $html = str_replace('<ol', '<br><ol', $html);
                $html = str_replace('<ul', '<br><ul', $html);

                $y    = $this->getY();
                $this->writeHTMLCell(12, 0, 12, $y, '', 0, 0, false, true, '', true);
                $this->writeHTMLCell(173, 0, 24, null, $html, 0, 1, false, true, '', true);

                $this->Ln(7);
            }
        }
    }
}
