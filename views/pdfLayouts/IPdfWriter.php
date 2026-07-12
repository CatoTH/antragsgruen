<?php

namespace app\views\pdfLayouts;

use app\models\db\MotionSection;
use setasign\Fpdi\Tcpdf\Fpdi;

class IPdfWriter extends Fpdi
{
    /**
     * Maps the class-based list styles of HTMLTools::KNOWN_OL_CLASSES to standard CSS list-style-types
     * natively supported by the tc-lib-pdf HTML renderer.
     * decimalCircle ("(1)") has no CSS equivalent and is handled separately in prepareHtmlListMarkup().
     */
    private const OL_CLASS_LIST_STYLES = [
        'decimalDot' => 'decimal',
        'lowerAlpha' => 'lower-alpha',
        'upperAlpha' => 'upper-alpha',
    ];
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

    /**
     * TCPDF 7 renders HTML through the tc-lib-pdf engine. The engine natively supports
     * <ol start="..."> and the standard CSS list-style-types, but neither our class-based list styles
     * (HTMLTools::KNOWN_OL_CLASSES) nor <li value="...">, so they are rewritten into supported markup:
     * - <ol class="decimalDot|lowerAlpha|upperAlpha"> => <ol style="list-style-type: ...">
     * - <ol class="decimalCircle"> => list-style-type:none, writing "(n)" directly into the <li>s
     * - <li value="n"> => the list is split into </ol><ol start="n"> (zeroing the margins at the split)
     * - in nested lists, a <p> directly at the beginning of a <li> is unwrapped, as the engine
     *   would otherwise render the list marker on a separate line above the paragraph
     * - plain lists nested inside a restyled list get an explicit default list-style-type, as the
     *   engine would let them inherit the outer list-style-type (unlike browsers with their UA stylesheet)
     */
    public static function prepareHtmlListMarkup(string $html): string
    {
        if (stripos($html, '<li') === false) {
            return $html;
        }

        $tokens = preg_split('/(<[^>]+>)/siu', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($tokens === false) {
            return $html;
        }
        $output = [];
        /** @var array<array{ordered: bool, circle: bool, styled: bool, counter: int|string, reopenAttrs: array<string, string>, openAttrs: array<string, string>, openIdx: int}> $listStack */
        $listStack = [];
        $pendingListMarker = null;
        $dropLiFirstParagraph = false;
        $dropParagraphClose = false;

        foreach ($tokens as $token) {
            if ($dropLiFirstParagraph && $token !== '') {
                if (preg_match('/^<p\b/siu', $token)) {
                    $dropLiFirstParagraph = false;
                    $dropParagraphClose = true;
                    continue;
                }
                if ($token[0] === '<' || trim($token) !== '') {
                    $dropLiFirstParagraph = false;
                }
            }
            if ($dropParagraphClose && preg_match('/^<\/p\s*>$/siu', $token)) {
                $dropParagraphClose = false;
                continue;
            }

            if ($pendingListMarker !== null && $token !== '') {
                if (preg_match('/^<(p|div|blockquote)\b/siu', $token)) {
                    // Write the marker inside the li's first block-level element; before it, it would force a line break
                    $output[] = $token;
                    $output[] = $pendingListMarker;
                    $pendingListMarker = null;
                    continue;
                }
                if ($token[0] === '<' || trim($token) !== '') {
                    $output[] = $pendingListMarker;
                    $pendingListMarker = null;
                }
                // whitespace-only text: emit it below and keep the marker pending
            }

            if (!preg_match('/^<(?<closing>\/?)(?<tag>ol|ul|li)\b/siu', $token, $tagMatch)) {
                $output[] = $token;
                continue;
            }
            $tagName = strtolower($tagMatch['tag']);

            if ($tagMatch['closing'] === '/') {
                if ($tagName !== 'li' && count($listStack) > 0) {
                    array_pop($listStack);
                }
                $output[] = $token;
                continue;
            }

            if ($tagName === 'ul') {
                $styled = false;
                if (self::hasStyledListInStack($listStack)) {
                    // The engine inherits list-style-type into nested lists, so a plain <ul> inside a
                    // restyled list would inherit e.g. "none". Set the default bullet explicitly.
                    $attrs = self::parseTagAttributes($token);
                    if (!self::hasOwnListStyle($attrs)) {
                        $attrs['style'] = (isset($attrs['style']) ? rtrim(trim($attrs['style']), ';') . ';' : '') . 'list-style-type:disc';
                        $token = self::buildTag('ul', $attrs);
                    }
                    $styled = true;
                }
                $listStack[] = ['ordered' => false, 'circle' => false, 'styled' => $styled, 'counter' => 1, 'reopenAttrs' => [], 'openAttrs' => [], 'openIdx' => count($output)];
                $output[] = $token;
                continue;
            }

            if ($tagName === 'ol') {
                $attrs = self::parseTagAttributes($token);
                $circle = false;
                $listStyle = null;
                if (isset($attrs['class'])) {
                    $classes = preg_split('/\s+/', trim($attrs['class']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    foreach ($classes as $i => $class) {
                        if ($class === 'decimalCircle') {
                            $circle = true;
                            unset($classes[$i]);
                        } elseif (isset(self::OL_CLASS_LIST_STYLES[$class])) {
                            $listStyle = self::OL_CLASS_LIST_STYLES[$class];
                            unset($classes[$i]);
                        }
                    }
                    if (count($classes) > 0) {
                        $attrs['class'] = implode(' ', $classes);
                    } else {
                        unset($attrs['class']);
                    }
                }
                if ($circle) {
                    $listStyle = 'none';
                }
                if ($listStyle === null && !self::hasOwnListStyle($attrs) && self::hasStyledListInStack($listStack)) {
                    // The engine inherits list-style-type into nested lists, so a plain <ol> inside a
                    // restyled list would inherit e.g. "none". Set the default numbering explicitly.
                    $listStyle = 'decimal';
                }
                if ($listStyle !== null) {
                    $attrs['style'] = (isset($attrs['style']) ? rtrim(trim($attrs['style']), ';') . ';' : '') . 'list-style-type:' . $listStyle;
                }

                $counter = (isset($attrs['start']) && preg_match('/^\d+$/', $attrs['start'])) ? intval($attrs['start']) : 1;
                $reopenAttrs = $attrs;
                unset($reopenAttrs['start']);

                $listStack[] = ['ordered' => true, 'circle' => $circle, 'styled' => ($listStyle !== null || self::hasOwnListStyle($attrs)), 'counter' => $counter, 'reopenAttrs' => $reopenAttrs, 'openAttrs' => $attrs, 'openIdx' => count($output)];
                $output[] = self::buildTag('ol', $attrs);
                continue;
            }

            // <li>
            $dropLiFirstParagraph = (count($listStack) >= 2);
            if (count($listStack) === 0 || !$listStack[count($listStack) - 1]['ordered']) {
                $output[] = $token;
                continue;
            }
            $currentList = &$listStack[count($listStack) - 1];

            $attrs = self::parseTagAttributes($token);
            if (isset($attrs['value'])) {
                $rawValue = $attrs['value'];
                $newCounter = self::listItemValueToNumber($rawValue);
                unset($attrs['value']);
                $token = self::buildTag('li', $attrs);
                if ($currentList['circle']) {
                    // The markers are written by us, so non-numeric values like "1b" can be shown verbatim
                    $currentList['counter'] = ($newCounter !== null ? $newCounter : $rawValue);
                } elseif ($newCounter !== null) {
                    $currentList['counter'] = $newCounter;

                    // Split the list into a new one starting at the given number. Zero out the margins
                    // at the split, as the renderer applies a default 1em vertical margin to lists.
                    $currentList['openAttrs']['style'] = (isset($currentList['openAttrs']['style']) ? rtrim(trim($currentList['openAttrs']['style']), ';') . ';' : '') . 'margin-bottom:0';
                    $output[$currentList['openIdx']] = self::buildTag('ol', $currentList['openAttrs']);

                    $reopenAttrs = $currentList['reopenAttrs'];
                    $reopenAttrs['start'] = (string)$newCounter;
                    $reopenAttrs['style'] = (isset($reopenAttrs['style']) ? rtrim(trim($reopenAttrs['style']), ';') . ';' : '') . 'margin-top:0';
                    $output[] = '</ol>';
                    $currentList['openAttrs'] = $reopenAttrs;
                    $currentList['openIdx'] = count($output);
                    $output[] = self::buildTag('ol', $reopenAttrs);
                }
            }
            $output[] = $token;
            if ($currentList['circle']) {
                $pendingListMarker = '(' . $currentList['counter'] . ')&nbsp;';
            }
            $currentList['counter'] = self::incrementListCounter($currentList['counter']);
            unset($currentList);
        }
        if ($pendingListMarker !== null) {
            $output[] = $pendingListMarker;
        }

        return implode('', $output);
    }

    /**
     * @param array<array{styled: bool}> $listStack
     */
    private static function hasStyledListInStack(array $listStack): bool
    {
        foreach ($listStack as $entry) {
            if ($entry['styled']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $attrs
     */
    private static function hasOwnListStyle(array $attrs): bool
    {
        return isset($attrs['style']) && preg_match('/list-style(-type)?\s*:/i', $attrs['style']) === 1;
    }

    /**
     * @return array<string, string>
     */
    private static function parseTagAttributes(string $tag): array
    {
        $attrs = [];
        preg_match_all('/(?<name>[a-z][a-z0-9_-]*)\s*=\s*(?:"(?<dq>[^"]*)"|\'(?<sq>[^\']*)\')/siu', $tag, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $attrs[strtolower($match['name'])] = (($match['dq'] ?? '') !== '' ? $match['dq'] : ($match['sq'] ?? ''));
        }

        return $attrs;
    }

    /**
     * @param array<string, string> $attrs
     */
    private static function buildTag(string $name, array $attrs): string
    {
        $tag = '<' . $name;
        foreach ($attrs as $attrName => $value) {
            $tag .= ' ' . $attrName . '="' . str_replace('"', '&quot;', $value) . '"';
        }

        return $tag . '>';
    }

    /**
     * Increments alphanumeric string counters like str_increment() ("1b" => "1c"),
     * which only exists as of PHP 8.3. Mimics the old TCPDF 6 behavior for such values.
     */
    private static function incrementListCounter(int|string $counter): int|string
    {
        if (is_int($counter)) {
            return $counter + 1;
        }
        if (!preg_match('/^[a-zA-Z0-9]+$/', $counter)) {
            return $counter;
        }

        $chars = str_split($counter);
        for ($i = count($chars) - 1; $i >= 0; $i--) {
            $char = $chars[$i];
            if ($char !== 'z' && $char !== 'Z' && $char !== '9') {
                $chars[$i] = chr(ord($char) + 1);

                return implode('', $chars);
            }
            // Overflow: wrap around and carry over to the character on the left
            $chars[$i] = ($char === 'z' ? 'a' : ($char === 'Z' ? 'A' : '0'));
        }

        $first = $counter[0];

        return (ctype_digit($first) ? '1' : ($first === strtoupper($first) ? 'A' : 'a')) . implode('', $chars);
    }

    private static function listItemValueToNumber(string $value): ?int
    {
        if (preg_match('/^\d+$/', $value)) {
            return intval($value);
        }
        if (preg_match('/^[a-z]$/', $value)) {
            return ord($value) - ord('a') + 1;
        }
        if (preg_match('/^[A-Z]$/', $value)) {
            return ord($value) - ord('A') + 1;
        }

        return null;
    }

    /**
     * @param string $html
     * @param bool $ln
     * @param bool $fill
     * @param bool $reseth
     * @param bool $cell
     * @param string $align
     */
    public function writeHTML($html, $ln = true, $fill = false, $reseth = false, $cell = false, $align = ''): void
    {
        parent::writeHTML(self::prepareHtmlListMarkup((string)$html), $ln, $fill, $reseth, $cell, $align);
    }

    /**
     * @param float $w
     * @param float $h
     * @param float|null $x
     * @param float|null $y
     * @param string $html
     * @param int|string|array<string, mixed> $border
     * @param int $ln
     * @param bool $fill
     * @param bool $reseth
     * @param string $align
     * @param bool $autopadding
     */
    public function writeHTMLCell($w, $h, $x, $y, $html = '', $border = 0, $ln = 0, $fill = false, $reseth = true, $align = '', $autopadding = true): void
    {
        parent::writeHTMLCell($w, $h, $x, $y, self::prepareHtmlListMarkup((string)$html), $border, $ln, $fill, $reseth, $align, $autopadding);
    }
}
