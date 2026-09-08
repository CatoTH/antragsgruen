<?php

declare(strict_types=1);

namespace app\components\diff;

use app\components\{HTMLTools, Tools, UrlHelper};
use app\models\db\Amendment;

class DiffRenderer
{
    public const FORMATTING_NONE = -1;
    public const FORMATTING_CLASSES = 0;
    public const FORMATTING_INLINE = 1;
    public const FORMATTING_ICE = 2; // For CKEditor LITE Change-Tracking
    public const FORMATTING_CLASSES_ARIA = 3;

    public const INS_START = '###INS_START###';
    public const INS_END   = '###INS_END###';
    public const DEL_START = '###DEL_START###';
    public const DEL_END   = '###DEL_END###';

    /**
     * Markers of the "outer" layer, used when two levels of changes are shown at once
     * (an amendment amending another amendment, see TwoLayerDiff).
     * The prefix is chosen so that the regular INS_START/DEL_START-patterns above cannot match them:
     * they all require three "#" immediately in front of "INS_"/"DEL_", which the prefix prevents.
     */
    public const MARKER_PREFIX_OUTER = 'OUTER_';

    public const OUTER_INS_START = '###OUTER_INS_START###';
    public const OUTER_INS_END   = '###OUTER_INS_END###';
    public const OUTER_DEL_START = '###OUTER_DEL_START###';
    public const OUTER_DEL_END   = '###OUTER_DEL_END###';

    /**
     * The CSS class marking the <ins>/<del>-elements of the outer layer.
     * Block elements cannot be wrapped into <ins>/<del> and carry the change in their class attribute instead
     * ("inserted"/"deleted"); as one and the same block can be changed by both layers at once, those need
     * a separate class name per layer.
     */
    public const CSS_CLASS_OUTER          = 'outer';
    public const CSS_CLASS_INSERTED_OUTER = 'insertedOuter';
    public const CSS_CLASS_DELETED_OUTER  = 'deletedOuter';

    private const MARKER_MATCH_ALL = '/###(?:OUTER_)?(?:INS|DEL)_(?:START|END)([^#]{0,20})###/siu';

    private \DOMDocument $nodeCreator;
    private int $formatting = 0;

    private string $markerPrefix = '';
    private string $insEnd;
    private string $delEnd;
    private string $insStartMatch;
    private string $delStartMatch;
    private string $insEndSplit;
    private string $delEndSplit;
    private string $anyStartSplit;

    /** @var null|callable */
    private $insCallback = null;
    /** @var null|callable */
    private $delCallback = null;

    public function __construct()
    {
        $this->nodeCreator = new \DOMDocument();
        $this->updateMarkerPatterns();
    }

    /**
     * Switches this renderer to a different set of markers, e.g. self::MARKER_PREFIX_OUTER.
     * Markers of the other layers are left untouched in the output and can be rendered by a subsequent pass.
     */
    public function setMarkerPrefix(string $markerPrefix): void
    {
        $this->markerPrefix = $markerPrefix;
        $this->updateMarkerPatterns();
    }

    private function updateMarkerPatterns(): void
    {
        $prefix              = $this->markerPrefix;
        $this->insEnd        = '###' . $prefix . 'INS_END###';
        $this->delEnd        = '###' . $prefix . 'DEL_END###';
        $this->insStartMatch = '/###' . $prefix . 'INS_START([^#]{0,20})###/siu';
        $this->delStartMatch = '/###' . $prefix . 'DEL_START([^#]{0,20})###/siu';
        $this->insEndSplit   = '/###' . $prefix . 'INS_END###/siu';
        $this->delEndSplit   = '/###' . $prefix . 'DEL_END###/siu';
        $this->anyStartSplit = '/(###' . $prefix . '(?:INS|DEL)_START([^#]{0,20})###)/siu';
    }

    public function setFormatting(int $formatting): void
    {
        $this->formatting = $formatting;

        if ($this->formatting == self::FORMATTING_ICE) {
            $this->setInsCallback(function ($node, $params) {
                /** @var \DOMElement $node */
                $classes = explode(' ', $node->getAttribute('class'));
                $classes = array_merge($classes, ['ice-cts', 'ice-ins']);
                $classes = array_filter($classes, function ($el) {
                    return ($el !== '');
                });
                $node->setAttribute('class', implode(' ', $classes));
                $node->setAttribute('data-userid', '');
                $node->setAttribute('data-username', '');
                $node->setAttribute('data-changedata', '');
                $node->setAttribute('data-time', (string)time());
                $node->setAttribute('data-last-change-time', (string)time());
            });
            $this->setDelCallback(function ($node, $params) {
                /** @var \DOMElement $node */
                $classes = explode(' ', $node->getAttribute('class'));
                $classes = array_merge($classes, ['ice-cts', 'ice-del']);
                $classes = array_filter($classes, function ($el) {
                    return ($el !== '');
                });
                $node->setAttribute('class', implode(' ', $classes));
                $node->setAttribute('data-userid', '');
                $node->setAttribute('data-username', '');
                $node->setAttribute('data-changedata', '');
                $node->setAttribute('data-time', (string)time());
                $node->setAttribute('data-last-change-time', (string)time());
            });
        }
    }

    public function setInsCallback(callable $callback): void
    {
        $this->insCallback = $callback;
    }

    public function setDelCallback(callable $callback): void
    {
        $this->delCallback = $callback;
    }

    public static function nodeCanBeAttachedToDelIns(\DOMNode $node): bool
    {
        if (is_a($node, \DOMText::class)) {
            return true;
        }
        /** @var \DOMElement $node */
        return !in_array($node->nodeName, HTMLTools::KNOWN_BLOCK_ELEMENTS);
    }

    public static function nodeAddClass(\DOMElement $node, string $cssClass): void
    {
        $prevClass = $node->getAttribute('class');
        if ($prevClass !== '') {
            $prevClass .= ' ';
        }
        $prevClass .= $cssClass;
        $node->setAttribute('class', $prevClass);
    }

    public static function nodeReplaceClass(\DOMElement $node, string $oldClass, string $newClass): void
    {
        $classes = explode(' ', $node->getAttribute('class'));
        $classes = array_map(fn (string $class) => ($class === $oldClass ? $newClass : $class), $classes);
        $node->setAttribute('class', implode(' ', $classes));
    }

    public static function nodeContainsText(\DOMNode $node, string $text): bool
    {
        return (str_contains($node->nodeValue, $text));
    }

    private function nodeStartInsDel(\DOMNode $node): bool
    {
        if (preg_match($this->insStartMatch, $node->nodeValue)) {
            return true;
        }
        if (preg_match($this->delStartMatch, $node->nodeValue)) {
            return true;
        }
        return false;
    }

    public static function nodeToPlainText(\DOMNode $node): string
    {
        $text = $node->nodeValue;
        $text = str_replace('###LINENUMBER###', '', $text);
        // Markers of the other layer(s) are still unrendered at this point and must not end up in aria-labels
        $text = preg_replace(self::MARKER_MATCH_ALL, '', $text);

        return trim(preg_replace('/\s{2,}/siu', ' ', $text));
    }

    private function createIns(string $param, \DOMNode $childNode): \DOMElement
    {
        $ins = $this->nodeCreator->createElement('ins');
        if ($this->formatting === self::FORMATTING_INLINE) {
            $ins->setAttribute('style', 'color: green; text-decoration: underline;');
        }
        if ($this->formatting === self::FORMATTING_CLASSES_ARIA) {
            $childText = self::nodeToPlainText($childNode);
            $text = str_replace('%INS%', $childText, \Yii::t('diff', 'aria_ins'));
            $ins->setAttribute('aria-label', $text);
        }
        if ($this->insCallback) {
            call_user_func($this->insCallback, $ins, $param);
        }
        return $ins;
    }

    private function createDel(string $param, \DOMNode $childNode): \DOMElement
    {
        $ins = $this->nodeCreator->createElement('del');
        if ($this->formatting === self::FORMATTING_INLINE) {
            $ins->setAttribute('style', 'color: red; text-decoration: line-through;');
        }
        if ($this->formatting === self::FORMATTING_CLASSES_ARIA) {
            $childText = self::nodeToPlainText($childNode);
            $text = str_replace('%DEL%', $childText, \Yii::t('diff', 'aria_del'));
            $ins->setAttribute('aria-label', $text);
        }
        if ($this->delCallback) {
            call_user_func($this->delCallback, $ins, $param);
        }
        return $ins;
    }

    private function addInsStyles(\DOMElement $element, string $param): void
    {
        self::nodeAddClass($element, 'inserted');
        if ($this->formatting === self::FORMATTING_INLINE) {
            $element->setAttribute('style', 'color: green; text-decoration: underline;');
        }
        if ($this->formatting === self::FORMATTING_CLASSES_ARIA) {
            $childText = self::nodeToPlainText($element);
            $text      = str_replace('%INS%', $childText, \Yii::t('diff', 'aria_ins'));
            $element->setAttribute('aria-label', $text);
        }
        if ($this->insCallback) {
            call_user_func($this->insCallback, $element, $param);
        }
    }

    private function addDelStyles(\DOMElement $element, string $param): void
    {
        self::nodeAddClass($element, 'deleted');
        if ($this->formatting === self::FORMATTING_INLINE) {
            $element->setAttribute('style', 'color: red; text-decoration: line-through;');
        }
        if ($this->formatting === self::FORMATTING_CLASSES_ARIA) {
            $childText = self::nodeToPlainText($element);
            $text      = str_replace('%DEL%', $childText, \Yii::t('diff', 'aria_del'));
            $element->setAttribute('aria-label', $text);
        }
        if ($this->delCallback) {
            call_user_func($this->delCallback, $element, $param);
        }
    }

    private function cloneNode(\DOMNode $node): \DOMNode
    {
        if (is_a($node, \DOMElement::class)) {
            /** @var \DOMElement $node */
            $newNode = $this->nodeCreator->createElement($node->nodeName);
            foreach ($node->attributes as $key => $val) {
                $val = $node->getAttribute($key);
                $newNode->setAttribute($key, $val);
            }
            foreach ($node->childNodes as $child) {
                $newNode->appendChild($this->cloneNode($child));
            }
            return $newNode;
        } else {
            /** @var \DOMText $node */
            $newNode = $this->nodeCreator->createTextNode($node->data);
            return $newNode;
        }
    }

    /**
     * @internal
     * @return array{array<\DOMNode>, ?string, ?string}
     */
    public function textToNodes(string $text, ?string $inIns, ?string $inDel, ?\DOMNode $lastEl): array
    {
        $nodes     = [];
        $lastIsIns = ($lastEl && is_a($lastEl, \DOMElement::class) && $lastEl->nodeName == 'ins');
        $lastIsDel = ($lastEl && is_a($lastEl, \DOMElement::class) && $lastEl->nodeName == 'del');
        while ($text !== '') {
            if ($inIns !== null) {
                $split = preg_split($this->insEndSplit, $text, 2);
                if ($split === false) {
                    throw new \RuntimeException('Could not parse text: ' . $text);
                }
                if ($split[0] !== '') {
                    $newText = $this->nodeCreator->createTextNode($split[0]);
                    if ($lastIsIns) {
                        $lastEl->appendChild($newText);
                    } else {
                        $newNode = $this->createIns($inIns, $newText);
                        $newNode->appendChild($newText);
                        $nodes[] = $newNode;
                    }
                }
                if (count($split) === 2) {
                    $text  = $split[1];
                    $inIns = null;
                } else {
                    $text = '';
                }
            } elseif ($inDel !== null) {
                $split = preg_split($this->delEndSplit, $text, 2);
                if ($split === false) {
                    throw new \RuntimeException('Could not parse text: ' . $text);
                }
                if ($split[0] !== '') {
                    $newText = $this->nodeCreator->createTextNode($split[0]);
                    if ($lastIsDel) {
                        $lastEl->appendChild($newText);
                    } else {
                        $newNode = $this->createDel($inDel, $newText);
                        $newNode->appendChild($newText);
                        $nodes[] = $newNode;
                    }
                }
                if (count($split) == 2) {
                    $text  = $split[1];
                    $inDel = null;
                } else {
                    $text = '';
                }
            } else {
                $split = preg_split($this->anyStartSplit, $text, 2, PREG_SPLIT_DELIM_CAPTURE);
                if ($split === false) {
                    throw new \RuntimeException('Could not parse text: ' . $text);
                }
                if (count($split) === 4) {
                    if ($split[0] !== '') {
                        $newText = $this->nodeCreator->createTextNode($split[0]);
                        $nodes[] = $newText;
                    }
                    $text = $split[3];
                    if (preg_match($this->insStartMatch, $split[1])) {
                        $inIns = $split[2];
                    } elseif (preg_match($this->delStartMatch, $split[1])) {
                        $inDel = $split[2];
                    }
                } else {
                    $newText = $this->nodeCreator->createTextNode($split[0]);
                    $nodes[] = $newText;
                    $text    = '';
                }
            }
            $lastEl    = null;
            $lastIsIns = $lastIsDel = false;
        }
        return [$nodes, $inIns, $inDel];
    }

    protected function renderHtmlWithPlaceholdersIntElement(\DOMElement $child, array &$newChildren, ?string &$inIns, ?string &$inDel): void
    {
        if ($inIns !== null && self::nodeContainsText($child, $this->insEnd)) {
            list($currNewChildren, $inIns, $inDel) = $this->renderHtmlWithPlaceholdersIntInIns($child, $inIns);
            $newChildren = array_merge($newChildren, $currNewChildren);
        } elseif ($inDel !== null && self::nodeContainsText($child, $this->delEnd)) {
            list($currNewChildren, $inIns, $inDel) = $this->renderHtmlWithPlaceholdersIntInDel($child, $inDel);
            $newChildren = array_merge($newChildren, $currNewChildren);
        } elseif ($inIns !== null) {
            $lastEl    = (count($newChildren) > 0 ? $newChildren[count($newChildren) - 1] : null);
            $prevIsIns = ($lastEl && is_a($lastEl, \DOMElement::class) && $lastEl->nodeName === 'ins');
            if ($prevIsIns && self::nodeCanBeAttachedToDelIns($child)) {
                $lastEl->appendChild(self::cloneNode($child));
            } elseif (self::nodeCanBeAttachedToDelIns($child)) {
                $clonedChild = self::cloneNode($child);
                $insNode     = $this->createIns($inIns, $clonedChild);
                $insNode->appendChild($clonedChild);
                $newChildren[] = $insNode;
            } else {
                /** @var \DOMElement $clone */
                $clone = self::cloneNode($child);
                $this->addInsStyles($clone, $inIns);
                $newChildren[] = $clone;
            }
        } elseif ($inDel !== null) {
            $lastEl    = (count($newChildren) > 0 ? $newChildren[count($newChildren) - 1] : null);
            $prevIsDel = ($lastEl && is_a($lastEl, \DOMElement::class) && $lastEl->nodeName == 'del');
            if ($prevIsDel && self::nodeCanBeAttachedToDelIns($child)) {
                $lastEl->appendChild(self::cloneNode($child));
            } elseif (self::nodeCanBeAttachedToDelIns($child)) {
                $clonedChild = self::cloneNode($child);
                $delNode     = $this->createDel($inDel, $clonedChild);
                $delNode->appendChild($clonedChild);
                $newChildren[] = $delNode;
            } else {
                /** @var \DOMElement $clone */
                $clone = self::cloneNode($child);
                $this->addDelStyles($clone, $inDel);
                $newChildren[] = $clone;
            }
        } else {
            list($currNewChildren, $inIns, $inDel) = $this->renderHtmlWithPlaceholdersIntNormal($child);
            $newChildren = array_merge($newChildren, $currNewChildren);
        }
    }

    protected function renderHtmlWithPlaceholdersIntInIns(\DOMNode $dom, ?string $inIns): array
    {
        if (!self::nodeContainsText($dom, $this->insEnd)) {
            return [[$this->cloneNode($dom)], $inIns, null];
        }

        $inDel = null;
        $newChildren = [];
        foreach ($dom->childNodes as $child) {
            if (is_a($child, \DOMText::class)) {
                /** @var \DOMText $child */
                $lastEl = (count($newChildren) > 0 ? $newChildren[count($newChildren) - 1] : null);
                list($currNewChildren, $inIns, $inDel) = $this->textToNodes($child->nodeValue, $inIns, $inDel, $lastEl);
                $newChildren = array_merge($newChildren, $currNewChildren);
            } elseif (is_a($child, \DOMElement::class)) {
                $this->renderHtmlWithPlaceholdersIntElement($child, $newChildren, $inIns, $inDel);
            }
        }

        $newDom = $this->nodeCreator->createElement($dom->nodeName);
        foreach ($newChildren as $newChild) {
            $newDom->appendChild($newChild);
        }
        foreach ($dom->attributes as $key => $val) {
            $val = $dom->getAttribute($key);
            $newDom->setAttribute($key, $val);
        }

        return [[$newDom], $inIns, $inDel];
    }

    protected function renderHtmlWithPlaceholdersIntInDel(\DOMNode $dom, ?string $inDel): array
    {
        if (!self::nodeContainsText($dom, $this->delEnd)) {
            return [[$this->cloneNode($dom)], null, $inDel];
        }

        $inIns = null;
        $newChildren = [];
        foreach ($dom->childNodes as $child) {
            if (is_a($child, \DOMText::class)) {
                /** @var \DOMText $child */
                $lastEl = (count($newChildren) > 0 ? $newChildren[count($newChildren) - 1] : null);
                list($currNewChildren, $inIns, $inDel) = $this->textToNodes($child->nodeValue, $inIns, $inDel, $lastEl);
                $newChildren = array_merge($newChildren, $currNewChildren);
            } elseif (is_a($child, \DOMElement::class)) {
                $this->renderHtmlWithPlaceholdersIntElement($child, $newChildren, $inIns, $inDel);
            }
        }

        $newDom = $this->nodeCreator->createElement($dom->nodeName);
        foreach ($newChildren as $newChild) {
            $newDom->appendChild($newChild);
        }

        return [[$newDom], $inIns, $inDel];
    }

    protected function renderHtmlWithPlaceholdersIntNormal(\DOMElement $dom): array
    {
        if (!$this->nodeStartInsDel($dom)) {
            return [[$this->cloneNode($dom)], null, null];
        }
        $inIns = $inDel = null;
        $newChildren = [];
        foreach ($dom->childNodes as $child) {
            if (is_a($child, \DOMText::class)) {
                /** @var \DOMText $child */
                $lastEl = (count($newChildren) > 0 ? $newChildren[count($newChildren) - 1] : null);
                list($currNewChildren, $inIns, $inDel) = $this->textToNodes($child->nodeValue, $inIns, $inDel, $lastEl);
                $newChildren = array_merge($newChildren, $currNewChildren);
            } elseif (is_a($child, \DOMElement::class)) {
                $this->renderHtmlWithPlaceholdersIntElement($child, $newChildren, $inIns, $inDel);
            }
        }

        $newDom = $this->nodeCreator->createElement($dom->nodeName);
        foreach ($dom->attributes as $key => $val) {
            $val = $dom->getAttribute($key);
            $newDom->setAttribute($key, $val);
        }
        foreach ($newChildren as $newChild) {
            $newDom->appendChild($newChild);
        }

        return [[$newDom], $inIns, $inDel];
    }

    public function renderHtmlWithPlaceholders(string $html): string
    {
        $dom = HTMLTools::html2DOM($html);
        $ret = $this->renderHtmlWithPlaceholdersIntNormal($dom);
        /** @var \DOMElement $body */
        $body = $ret[0][0];
        $str  = '';
        foreach ($body->childNodes as $child) {
            $str .= HTMLTools::renderDomToHtml($child);
        }
        return $str;
    }

    /**
     * Renders a string containing both the regular markers and the ones of the outer layer
     * (###OUTER_INS_START### etc., as produced by TwoLayerDiff).
     *
     * This is done in two passes: the outer markers are rendered first, the inner ones afterwards. As TwoLayerDiff
     * guarantees that the inner markers never cross the boundaries of an outer one, the second pass only ever sees
     * properly nested input and the regular, single-level state machine is sufficient for both passes.
     */
    public static function renderTwoLayerHtmlWithPlaceholders(string $html, int $formatting): string
    {
        $markAsOuter = function (\DOMElement $node, string $inlineTag, string $blockClass): void {
            if ($node->nodeName === $inlineTag) {
                self::nodeAddClass($node, self::CSS_CLASS_OUTER);
            } else {
                self::nodeReplaceClass($node, ($inlineTag === 'ins' ? 'inserted' : 'deleted'), $blockClass);
            }
        };

        $outerRenderer = new DiffRenderer();
        $outerRenderer->setMarkerPrefix(self::MARKER_PREFIX_OUTER);
        $outerRenderer->setFormatting($formatting);
        $outerRenderer->setInsCallback(function ($node, $params) use ($markAsOuter) {
            $markAsOuter($node, 'ins', self::CSS_CLASS_INSERTED_OUTER);
        });
        $outerRenderer->setDelCallback(function ($node, $params) use ($markAsOuter) {
            $markAsOuter($node, 'del', self::CSS_CLASS_DELETED_OUTER);
        });

        $innerRenderer = new DiffRenderer();
        $innerRenderer->setFormatting($formatting);

        $rendered = $innerRenderer->renderHtmlWithPlaceholders($outerRenderer->renderHtmlWithPlaceholders($html));

        // Rendering the two layers separately can leave empty elements behind, e.g. when an insertion of the inner
        // layer covers a whole block element that the outer layer marks as deleted.
        do {
            $prev     = $rendered;
            $rendered = preg_replace('/<(ins|del)( [^>]*)?><\/\1>/siu', '', $rendered);
        } while ($rendered !== $prev);

        return $rendered;
    }

    private static function paragraphContainsDiff_getPos(string $line, array $matches): int
    {
        // Workaround: PREG_OFFSET_CAPTURE ignores utf-8
        $strBefore = substr($line, 0, $matches[0][1]);
        $strBefore = (string) mb_convert_encoding($strBefore, 'ISO-8859-1', 'UTF-8');
        return strlen($strBefore);
    }

    /**
     * Returns the position of the first change of this paragraph, or null if it does not contain any.
     *
     * Changes of the outer layer (see TwoLayerDiff) do not count: within a two-layered diff, they are the
     * context the changes are shown in, not the changes themselves.
     */
    public static function paragraphContainsDiff(string $line): ?int
    {
        $firstDiffs = [];
        if (preg_match('/(<ins(?![^>]*[ "\']' . self::CSS_CLASS_OUTER . '[ "\'])( [^>]*)?>)/siu', $line, $matches, PREG_OFFSET_CAPTURE)) {
            $firstDiffs[] = self::paragraphContainsDiff_getPos($line, $matches);
        }
        if (preg_match('/(<del(?![^>]*[ "\']' . self::CSS_CLASS_OUTER . '[ "\'])( [^>]*)?>)/siu', $line, $matches, PREG_OFFSET_CAPTURE)) {
            $firstDiffs[] = self::paragraphContainsDiff_getPos($line, $matches);
        }
        if (preg_match('/(<[^>]+[ "]inserted[ "][^>]*>)/siu', $line, $matches, PREG_OFFSET_CAPTURE)) {
            $firstDiffs[] = self::paragraphContainsDiff_getPos($line, $matches);
        }
        if (preg_match('/(<[^>]+[ "]deleted[ "][^>]*>)/siu', $line, $matches, PREG_OFFSET_CAPTURE)) {
            $firstDiffs[] = self::paragraphContainsDiff_getPos($line, $matches);
        }
        if (count($firstDiffs) === 0) {
            return null;
        }
        return min($firstDiffs);
    }

    public static function getAmendmentInlineChangeData(Amendment $amendment, string $changeId): array
    {
        if ($amendment->status === Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT) {
            return static::getAmendmentInlineChangeData($amendment->proposalReferencedByAmendment->getAmendment(), $changeId);
        }
        if ($amendment->status === Amendment::STATUS_PROPOSED_MODIFIED_MOTION) {
            $time = Tools::dateSql2timestamp($amendment->dateCreation) * 1000;
            $motion = $amendment->proposalReferencedByMotion->getMotion();
            return [
                'data-cid'              => $changeId,
                'data-userid'           => '',
                'data-username'         => '',
                'data-changedata'       => '',
                'data-time'             => $time,
                'data-last-change-time' => $time,
                'data-append-hint'      => '[' . \Yii::t('diff', 'modu_prefix') . ']',
                'data-link'             => UrlHelper::createMotionUrl($motion),
                'data-amendment-id'     => null,
                'data-is-modu'          => 1,
            ];
        }
        $time = Tools::dateSql2timestamp($amendment->dateCreation) * 1000;
        return [
            'data-cid'              => $changeId,
            'data-userid'           => '',
            'data-username'         => $amendment->getInitiatorsStr(),
            'data-changedata'       => '',
            'data-time'             => $time,
            'data-last-change-time' => $time,
            'data-append-hint'      => '[' . $amendment->getFormattedTitlePrefix() . ']',
            'data-link'             => UrlHelper::createAmendmentUrl($amendment),
            'data-amendment-id'     => $amendment->id,
        ];
    }

    /**
     * @param Amendment[] $amendmentsById
     */
    public static function renderForInlineDiff(string $diff, array $amendmentsById): string
    {
        $renderer = new DiffRenderer();
        $renderer->setInsCallback(function ($node, $params) use ($amendmentsById) {
            /** @var \DOMElement $node */
            $params    = explode('-', $params);
            $amendment = $amendmentsById[$params[1]];
            foreach (static::getAmendmentInlineChangeData($amendment, $params[0]) as $key => $val) {
                $node->setAttribute($key, (string)$val);
            }
            $classes = explode(' ', $node->getAttribute('class'));
            $classes = array_merge($classes, ['ice-ins', 'ice-cts', 'appendHint']);
            foreach ($amendment->getPublicTopicTags() as $tag) {
                $classes[] = 'tag-' . $tag->getNameBasedCSSClass();
            }
            if (count($params) > 2 && $params[2] === 'COLLISION') {
                $classes[] = 'appendedCollision';
                $node->setAttribute('data-appended-collision', '1');
            }
            $node->setAttribute('class', implode(' ', $classes));

        });
        $renderer->setDelCallback(function ($node, $params) use ($amendmentsById) {
            /** @var \DOMElement $node */
            $params    = explode('-', $params);
            $amendment = $amendmentsById[$params[1]];
            foreach (static::getAmendmentInlineChangeData($amendment, $params[0]) as $key => $val) {
                $node->setAttribute($key, (string)$val);
            }
            $classes = explode(' ', $node->getAttribute('class'));
            $classes = array_merge($classes, ['ice-del', 'ice-cts', 'appendHint']);
            foreach ($amendment->getPublicTopicTags() as $tag) {
                $classes[] = 'tag-' . $tag->getNameBasedCSSClass();
            }
            if (count($params) > 2 && $params[2] === 'COLLISION') {
                $classes[] = 'appendedCollision';
                $node->setAttribute('data-appended-collision', '1');
            }
            $node->setAttribute('class', implode(' ', $classes));
        });
        return $renderer->renderHtmlWithPlaceholders($diff);
    }
}
