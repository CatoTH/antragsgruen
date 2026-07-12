<?php

namespace app\views\pdfLayouts;

use app\components\HTMLTools;

/**
 * Extends the tc-lib-pdf rendering engine used by the TCPDF legacy facade to support
 * Antragsgrün's non-standard list rendering (see HTMLTools for the reference implementation):
 *
 * - <ol> list styles set via class attribute (decimalDot, decimalCircle, lowerAlpha, upperAlpha),
 *   e.g. rendering "(1)" instead of "1." for decimalCircle.
 * - Explicitly set values on <li> tags, including non-standard values like "1b".
 *   Numbers and single letters (a/A=1, b/B=2, ...) set the list counter, so counting
 *   continues from there; other values leave the counter unaffected.
 * - The markers are still rendered separately, left of the actual text block.
 *
 * Additionally, some vertical spacing behaviors of TCPDF 6 that IPdfWriter::printMotionSection()
 * and the PDF layouts rely on are restored.
 *
 * @phpstan-import-type THTMLRenderContext from \Com\Tecnick\Pdf\HTML
 * @phpstan-import-type THTMLAttrib from \Com\Tecnick\Pdf\HTML
 */
class IPdfWriterEngine extends \TCPDF_ENGINE
{
    /** Block tags that should share the line with the list marker when they are the first child of a <li> */
    private const LI_INLINE_BLOCK_TAGS = ['p', 'div', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre'];

    /** Closed block elements after which TCPDF 6 added an extra line break when a list was opened.
     * Must match the block elements checked in IPdfWriter::printMotionToPDFAddLinebreaks(). */
    private const LEGACY_BLOCK_CLOSE_TAGS = ['div', 'p', 'blockquote', 'ul', 'ol', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    /** @var string|null The value attribute of the list item currently being opened, to be rendered verbatim */
    private ?string $currentLiValue = null;

    /**
     * @param THTMLRenderContext $hrc
     */
    protected function parseHTMLTagOPENol(array &$hrc, int $key, float &$tpx, float &$tpy, float &$tpw, float &$tph): string
    {
        $this->applyLegacyListSpacing($hrc, $key, $tpy);

        return parent::parseHTMLTagOPENol($hrc, $key, $tpx, $tpy, $tpw, $tph);
    }

    /**
     * @param THTMLRenderContext $hrc
     */
    protected function parseHTMLTagOPENul(array &$hrc, int $key, float &$tpx, float &$tpy, float &$tpw, float &$tph): string
    {
        $this->applyLegacyListSpacing($hrc, $key, $tpy);

        return parent::parseHTMLTagOPENul($hrc, $key, $tpx, $tpy, $tpw, $tph);
    }

    /**
     * TCPDF 6 performed a line break when opening an ol/ul in situations where the new engine
     * does not, resulting in one empty line at the beginning of a list. The markup created by
     * IPdfWriter::printMotionSection() and the line numbers printed next to motion texts rely
     * on exactly this behavior, so it is replicated here.
     * (The spacing between list items via "</li><br><li>"-markup needs no special handling,
     * as the <br> creates the empty line by itself.)
     *
     * @param THTMLRenderContext $hrc
     */
    private function applyLegacyListSpacing(array &$hrc, int $key, float &$tpy): void
    {
        for ($k = $key - 1; $k >= 0; $k--) {
            $node = $hrc['dom'][$k] ?? null;
            if ($node === null) {
                return;
            }
            if (!$node['tag']) {
                if (trim($node['value']) === '') {
                    continue; // whitespace between tags
                }

                return; // inline text directly before: the parent method breaks the line itself
            }

            if ($node['value'] === 'br') {
                // an explicit line break directly before: TCPDF 6 added another one when opening the list
                $tpy += $this->getHTMLLineAdvance($hrc, $key);
            } elseif (!$node['opening']) {
                // a closed block element directly before, like a previous list or paragraph
                if (in_array($node['value'], self::LEGACY_BLOCK_CLOSE_TAGS, true)) {
                    $tpy += $this->getHTMLLineAdvance($hrc, $key);
                }
            } else {
                // an opening tag directly before is the parent element: the list is the first
                // content of the cell or of its surrounding block element
                $tpy += $this->getHTMLLineAdvance($hrc, $key);
            }

            return;
        }
    }

    /**
     * The parent method applies a default 1em top/bottom margin to p/ol/ul/dl/blockquote/pre.
     * Legacy TCPDF semantics, which the PDF layouts (and the line numbering next to motion texts)
     * rely on: for tags explicitly configured through setHtmlVSpace(), that configuration defines
     * the full vertical spacing - no default margins are added on top.
     *
     * @param array<int, THTMLAttrib> $dom
     */
    protected function parseHTMLAttributesBlockMargins(array &$dom, int $key): void
    {
        $node = $dom[$key] ?? null;
        if ($node !== null && isset($this->tagvspaces[$node['value']])) {
            return;
        }

        parent::parseHTMLAttributesBlockMargins($dom, $key);
    }

    /**
     * The parent method starts a new line for every block element unless it is at the very top
     * of the rendered cell. For a block element (like <p>) that is the first child of a <li>,
     * this would place the text one line below the list marker. Suppress the vertical advance
     * in this case, so the text starts on the same line as the marker.
     * Nested <ol>/<ul> as the first child keep the line break, so their markers do not collide
     * with the parent list's marker.
     *
     * @param THTMLRenderContext $hrc
     */
    protected function openHTMLBlock(array &$hrc, int $key, float &$tpx, float &$tpy, float &$tpw): string
    {
        if ($this->isFirstBlockOfListItem($hrc, $key)) {
            $prevOriginY = $hrc['cellctx']['originy'];
            // Replicate the top-of-cell situation, in which the parent method applies no advance
            $hrc['cellctx']['originy'] = $tpy;
            $hrc['cellctx']['lineadvance'] = 0.0;
            $hrc['cellctx']['linebottom'] = 0.0;
            try {
                return parent::openHTMLBlock($hrc, $key, $tpx, $tpy, $tpw);
            } finally {
                $hrc['cellctx']['originy'] = $prevOriginY;
            }
        }

        return parent::openHTMLBlock($hrc, $key, $tpx, $tpy, $tpw);
    }

    /**
     * @param THTMLRenderContext $hrc
     */
    private function isFirstBlockOfListItem(array &$hrc, int $key): bool
    {
        $elm = $hrc['dom'][$key] ?? null;
        if ($elm === null || !in_array($elm['value'], self::LI_INLINE_BLOCK_TAGS, true)) {
            return false;
        }

        $parent = $hrc['dom'][$elm['parent']] ?? null;
        if ($parent === null || $parent['value'] !== 'li' || !$parent['tag']) {
            return false;
        }

        // Only if nothing except whitespace comes between the <li> and this element
        for ($k = $elm['parent'] + 1; $k < $key; $k++) {
            $node = $hrc['dom'][$k] ?? null;
            if ($node === null || $node['tag'] || trim($node['value']) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param THTMLRenderContext $hrc
     */
    protected function getHTMLListMarkerType(array &$hrc, int $key, bool $ordered): string
    {
        if ($ordered) {
            $elm = $hrc['dom'][$key] ?? null;
            if ($elm !== null && isset($elm['attribute']['class']) && is_string($elm['attribute']['class'])) {
                $classes = explode(' ', $elm['attribute']['class']);
                foreach (HTMLTools::KNOWN_OL_CLASSES as $olClass) {
                    if (in_array($olClass, $classes, true)) {
                        return $olClass;
                    }
                }
            }
        }

        return parent::getHTMLListMarkerType($hrc, $key, $ordered);
    }

    /**
     * @param THTMLRenderContext $hrc
     */
    protected function getHTMLListItemCounter(array &$hrc, int $key): int
    {
        $this->currentLiValue = null;

        $depth = $this->getHTMLListDepth($hrc);
        if ($depth < 1) {
            return 1;
        }

        $idx = $depth - 1;
        $listEntry = $hrc['liststack'][$idx] ?? null;
        if ($listEntry === null || !$listEntry['ordered']) {
            return 1;
        }

        $newCount = $listEntry['count'] + 1;

        $elm = $hrc['dom'][$key] ?? null;
        $value = ($elm !== null && isset($elm['attribute']['value']) && is_string($elm['attribute']['value'])) ? $elm['attribute']['value'] : '';
        if ($value !== '') {
            // Same semantics as HTMLTools::getNextLiCounter(): numbers and single letters
            // set the counter, any other value (like "1b") leaves it unaffected
            if (is_numeric($value)) {
                $newCount = intval($value);
            } elseif (preg_match('/^[a-zA-Z]$/', $value)) {
                $newCount = ord(strtolower($value)) - ord('a') + 1;
            }
            // Explicitly set values are always rendered verbatim, as in HTMLTools::getLiValue()
            $this->currentLiValue = $value;
        }

        $hrc['liststack'][$idx]['count'] = $newCount;

        return $newCount;
    }

    /**
     * @param array<string, mixed> $markerStyles
     */
    protected function getHTMLliBullet(
        int $depth,
        int $count,
        float $posx = 0,
        float $posy = 0,
        string $type = '',
        array $markerStyles = [],
    ): string {
        $explicitValue = $this->currentLiValue;
        $this->currentLiValue = null;

        $isCustomFormat = in_array($type, HTMLTools::KNOWN_OL_CLASSES, true);
        if ($explicitValue === null && !$isCustomFormat) {
            return parent::getHTMLliBullet($depth, $count, $posx, $posy, $type, $markerStyles);
        }

        $format = $isCustomFormat ? $type : HTMLTools::OL_DECIMAL_DOT;
        $text = HTMLTools::getLiValueFormatted($count, $explicitValue, $format);

        // Replicates the text marker rendering of the parent method for ordered lists,
        // drawing the marker left of the list item's text block
        $markerState = [];
        $markerPrefix = '';
        if ($markerStyles !== []) {
            $markerPrefix = $this->getStartMarkerStyle($markerStyles, $markerState);
        }

        $lspace = $this->getStringWidth(' ') + $this->getStringWidth($text);
        $posx += $this->rtl ? $lspace : -$lspace;

        $out = $this->getTextLine($text, $posx, $posy);

        if ($markerPrefix !== '') {
            $out = $markerPrefix . $out;
        }
        if ($markerStyles !== []) {
            $out .= $this->getStopMarkerStyle($markerState);
        }

        return $out;
    }
}
