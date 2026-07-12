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
 */
class IPdfWriterEngine extends \TCPDF_ENGINE
{
    /** @var string|null The value attribute of the list item currently being opened, to be rendered verbatim */
    private ?string $currentLiValue = null;

    /**
     * @param array<mixed> $hrc
     */
    protected function getHTMLListMarkerType(array &$hrc, int $key, bool $ordered): string
    {
        if ($ordered) {
            $elm = $hrc['dom'][$key] ?? null;
            if (\is_array($elm) && isset($elm['attribute']['class'])) {
                $classes = explode(' ', (string) $elm['attribute']['class']);
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
     * @param array<mixed> $hrc
     */
    protected function getHTMLListItemCounter(array &$hrc, int $key): int
    {
        $this->currentLiValue = null;

        $depth = $this->getHTMLListDepth($hrc);
        if ($depth < 1) {
            return 1;
        }

        $idx = $depth - 1;
        $listEntry = $hrc['liststack'][$idx] ?? [];
        if (!($listEntry['ordered'] ?? false)) {
            return 1;
        }

        $newCount = ($listEntry['count'] ?? 0) + 1;

        $elm = $hrc['dom'][$key] ?? null;
        $value = (\is_array($elm) && isset($elm['attribute']['value'])) ? (string) $elm['attribute']['value'] : '';
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
