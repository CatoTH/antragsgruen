<?php

namespace app\views\pdfLayouts;

use app\models\db\MotionSection;
use setasign\Fpdi\Tcpdf\Fpdi;

class IPdfWriter extends Fpdi
{
    /**
     * This adds <br>-tags where necessary.
     * Test cases are collected in the "Listen-Test"-motion.
     * Check in the TCPDF-generated PDF that line numbers match the lines.
     *
     * @param string[] $linesArr
     *
     * @return string[]
     */
    private function printMotionToPDFAddLinebreaks($linesArr)
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

    public function getMotionFont(MotionSection $section): string
    {
        if ($section->getSettings()->fixedWidth) {
            return 'dejavusansmono';
        } else {
            return 'helvetica';
        }
    }

    public function getMotionFontSize(MotionSection $section): int
    {
        $lineLength = $section->getConsultation()->getSettings()->lineLength;

        return ($lineLength > 70 ? 10 : 11);
    }

    public function printMotionSection(MotionSection $section): void
    {
        $linenr   = $section->getFirstLineNumber();
        $textSize = $this->getMotionFontSize($section);
        $fontName = $this->getMotionFont($section);

        $this->SetFont($fontName, '', $textSize);
        $this->Ln(7);

        $hasLineNumbers = $section->getSettings()->lineNumbers;
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
                $this->writeHTMLCell(12, '', 12, $y, $text2, 0, 0, 0, true, '', true);

                $this->SetFont($fontName, '', $textSize);
                $this->SetTextColor(0, 0, 0);
                $this->setCellHeightRatio(1.5);
                $linesArr = $this->printMotionToPDFAddLinebreaks($linesArr);
                $text1    = implode('<br>', $linesArr);

                // instead of <span class="strike"></span> TCPDF can only handle <s></s>
                // for striking through text
                $text1 = preg_replace('/<span class="strike">(.*)<\/span>/iUs', '<s>${1}</s>', $text1);

                // instead of <span class="underline"></span> TCPDF can only handle <u></u>
                // for underlined text
                $text1 = preg_replace('/<span class="underline">(.*)<\/span>/iUs', '<u>${1}</u>', $text1);

                $this->writeHTMLCell(173, '', 24, $y, $text1, 0, 1, 0, true, '', true);

                $this->Ln(7);
            }
        } else {
            $paras = $section->getTextParagraphLines();
            foreach ($paras as $para) {
                $html = str_replace('###LINENUMBER###', '', implode('', $para));
                $y    = $this->getY();
                $this->writeHTMLCell(12, '', 12, $y, '', 0, 0, 0, true, '', true);
                $this->writeHTMLCell(173, '', 24, '', $html, 0, 1, 0, true, '', true);

                $this->Ln(7);
            }
        }
    }
}
