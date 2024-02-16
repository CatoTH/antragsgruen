<?php

namespace app\views\pdfLayouts;

use app\components\HTMLTools;
use app\models\db\MotionSection;
use setasign\Fpdi\Tcpdf\Fpdi;
use TCPDF_STATIC;

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

    protected function openHTMLTagHandler($dom, $key, $cell)
    {
        $return = parent::openHTMLTagHandler($dom, $key, $cell);

        $tag    = $dom[$key];
        $parent = $dom[($dom[$key]['parent'])];

        switch ($tag['value']) {
            case 'ol':
                if (isset($tag['attribute']['start'])) {
                    $this->listcount[$this->listnum] = intval($tag['attribute']['start']) - 1;
                } else {
                    $this->listcount[$this->listnum] = 0;
                }
                break;
            case 'li':
                if ($this->listordered[$this->listnum]) {
                    if (isset($tag['attribute']['value'])) {
                        if (preg_match('/^\d+$/siu', $tag['attribute']['value'])) {
                            $this->listcount[$this->listnum] = intval($tag['attribute']['value']);
                        } elseif (preg_match('/^[a-z]$/siu', $tag['attribute']['value'])) {
                            $this->listcount[$this->listnum] = ord($tag['attribute']['value']) - ord("a") + 1;
                        } elseif (preg_match('/^[A-Z]$/siu', $tag['attribute']['value'])) {
                            $this->listcount[$this->listnum] = ord((string)intval($tag['attribute']['value'])) - ord("A") + 1;
                        } else {
                            $this->listcount[$this->listnum] = $tag['attribute']['value'];
                        }
                    }
                }
                if (isset($parent['attribute']['class']) AND !TCPDF_STATIC::empty_string($parent['attribute']['class'])) {
                    $classes = explode(" ", $parent['attribute']['class']);
                    foreach (HTMLTools::KNOWN_OL_CLASSES as $olClass) {
                        if (in_array($olClass, $classes)) {
                            $this->lispacer = $olClass;
                        }
                    }
                }
                break;
        }

        return $return;
    }

    public function setLIsymbol($symbol = '!'): void
    {
        // check for custom image symbol
        if (substr($symbol, 0, 4) == 'img|') {
            $this->lisymbol = $symbol;

            return;
        }
        $symbol        = strtolower($symbol);
        $valid_symbols = [
            '!',
            '#',
            'disc',
            'circle',
            'square',
            '1',
            'decimal',
            'decimal-leading-zero',
            'i',
            'lower-roman',
            'I',
            'upper-roman',
            'a',
            'lower-alpha',
            'lower-latin',
            'A',
            'upper-alpha',
            'upper-latin',
            'lower-greek'
        ];
        $valid_symbols = array_merge($valid_symbols, HTMLTools::KNOWN_OL_CLASSES);
        if (in_array($symbol, $valid_symbols)) {
            $this->lisymbol = $symbol;
        } else {
            $this->lisymbol = '';
        }
    }

    protected function putHtmlListBullet($listdepth, $listtype = '', $size = 10): void
    {
        if ($this->state != 2) {
            return;
        }

        $size        /= $this->k;
        $bgcolor     = $this->bgcolor;
        $color       = $this->fgcolor;
        $strokecolor = $this->strokecolor;
        $textitem    = '';
        $tmpx        = $this->x;
        $lspace      = (float)$this->GetStringWidth('  ');
        if ($listtype == '^') {
            // special symbol used for avoid justification of rect bullet
            $this->lispacer = '';

            return;
        } elseif ($listtype == '!') {
            // set default list type for unordered list
            $deftypes = ['disc', 'circle', 'square'];
            $listtype = $deftypes[($listdepth - 1) % 3];
        } elseif ($listtype == '#') {
            // set default list type for ordered list
            $listtype = 'decimal';
        }

        switch ($listtype) {
            // unordered types
            case 'none':
            {
                break;
            }
            case 'disc':
            {
                $r      = $size / 6;
                $lspace += (2 * $r);
                if ($this->rtl) {
                    $this->x += $lspace;
                } else {
                    $this->x -= $lspace;
                }
                $this->Circle(($this->x + $r), ($this->y + ($this->lasth / 2)), $r, 0, 360, 'F', [], $color, 8);
                break;
            }
            case 'circle':
            {
                $r      = $size / 6;
                $lspace += (2 * $r);
                if ($this->rtl) {
                    $this->x += $lspace;
                } else {
                    $this->x -= $lspace;
                }
                $prev_line_style = $this->linestyleWidth . ' ' . $this->linestyleCap . ' ' . $this->linestyleJoin . ' ' . $this->linestyleDash . ' ' . $this->DrawColor;
                $new_line_style  = ['width' => ($r / 3), 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'phase' => 0, 'color' => $color];
                $this->Circle(($this->x + $r), ($this->y + ($this->lasth / 2)), ($r * (1 - (1 / 6))), 0, 360, 'D', $new_line_style, [], 8);
                $this->_out($prev_line_style); // restore line settings
                break;
            }
            case 'square':
            {
                $l      = $size / 3;
                $lspace += $l;
                if ($this->rtl) {
                    ;
                    $this->x += $lspace;
                } else {
                    $this->x -= $lspace;
                }
                $this->Rect($this->x, ($this->y + (($this->lasth - $l) / 2)), $l, $l, 'F', [], $color);
                break;
            }
            // ordered types
            // $this->listcount[$this->listnum];
            // $textitem
            case '1':
            case 'decimalCircle':
            case 'decimal':
            {
                $textitem = $this->listcount[$this->listnum];
                break;
            }
            case 'decimal-leading-zero':
            {
                $textitem = sprintf('%02d', $this->listcount[$this->listnum]);
                break;
            }
            case 'i':
            case 'lower-roman':
            {
                $textitem = strtolower(TCPDF_STATIC::intToRoman($this->listcount[$this->listnum]));
                break;
            }
            case 'I':
            case 'upper-roman':
            {
                $textitem = TCPDF_STATIC::intToRoman($this->listcount[$this->listnum]);
                break;
            }
            case 'a':
            case 'lower-alpha':
            case 'lowerAlpha':
            case 'lower-latin':
            {
                if (is_int($this->listcount[$this->listnum])) {
                    $textitem = chr(97 + $this->listcount[$this->listnum] - 1);
                } else {
                    $textitem = $this->listcount[$this->listnum];
                }
                break;
            }
            case 'A':
            case 'upper-alpha':
            case 'upperAlpha':
            case 'upper-latin':
            {
                if (is_int($this->listcount[$this->listnum])) {
                    $textitem = chr(65 + $this->listcount[$this->listnum] - 1);
                } else {
                    $textitem = $this->listcount[$this->listnum];
                }
                break;
            }
            default:
            {
                $textitem = $this->listcount[$this->listnum];
            }
        }
        if (!TCPDF_STATIC::empty_string($textitem)) {
            // Check whether we need a new page or new column
            $prev_y = $this->y;
            $h      = $this->getCellHeight($this->FontSize);
            if ($this->checkPageBreak($h) OR ($this->y < $prev_y)) {
                $tmpx = $this->x;
            }
            // print ordered item
            if ($listtype === 'decimalCircle') {
                $textitem = '(' . $textitem . ')';
            } else {
                if ($this->rtl) {
                    $textitem = '.' . $textitem;
                } else {
                    $textitem = $textitem . '.';
                }
            }
            $strWidth = (float)$this->GetStringWidth($textitem);
            $lspace += $strWidth;
            if ($this->rtl) {
                $this->x += $lspace;
            } else {
                $this->x -= $lspace;
            }
            $this->Write($this->lasth, $textitem, '', false, '', false, 0, false);
        }
        $this->x        = $tmpx;
        $this->lispacer = '^';
        // restore colors
        $this->SetFillColorArray($bgcolor);
        $this->SetDrawColorArray($strokecolor);
        $this->SettextColorArray($color);
    }
}
