<?php

namespace app\components\diff;

use app\components\HTMLTools;
use app\models\db\Amendment;

class DiffRenderer
{
    const FORMATTING_NONE    = -1;
    const FORMATTING_CLASSES = 0;
    const FORMATTING_INLINE  = 1;

    const INS_START = '###INS_START###';
    const INS_END   = '###INS_END###';
    const DEL_START = '###DEL_START###';
    const DEL_END   = '###DEL_END###';

    const INS_START_MATCH = '/###INS_START([^#]{0,20})###/siu';
    const DEL_START_MATCH = '/###DEL_START([^#]{0,20})###/siu';

    /** @var \DOMDocument */
    private $nodeCreator;

    /** @var int */
    private $formatting = 0;

    /** @var null|callable */
    private $insCallback = null;
    /** @var null|callable */
    private $delCallback = null;

    /**
     */
    public function __construct()
    {
        $this->nodeCreator = new \DOMDocument();
    }

    /**
     * @param int $formatting
     */
    public function setFormatting($formatting)
    {
        $this->formatting = $formatting;
    }

    /**
     * @param callable $callback
     */
    public function setInsCallback($callback)
    {
        $this->insCallback = $callback;
    }

    /**
     * @param callable $callback
     */
    public function setDelCallback($callback)
    {
        $this->delCallback = $callback;
    }

    /**
     * @internal
     * @param \DOMNode $node
     * @return boolean
     */
    public static function nodeCanBeAttachedToDelIns($node)
    {
        if (is_a($node, \DOMText::class)) {
            return true;
        }
        /** @var \DOMElement $node */
        return !in_array($node->nodeName, HTMLTools::$KNOWN_BLOCK_ELEMENTS);
    }

    /**
     * @internal
     * @param \DOMElement $node
     * @param string $cssClass
     */
    public static function nodeAddClass(\DOMElement $node, $cssClass)
    {
        $prevClass = $node->getAttribute('class');
        if ($prevClass != '') {
            $prevClass .= ' ';
        }
        $prevClass .= $cssClass;
        $node->setAttribute('class', $prevClass);
    }

    /**
     * @internal
     * @param \DOMNode $node
     * @param string $text
     * @return bool
     */
    public static function nodeContainsText($node, $text)
    {
        return (mb_strpos($node->nodeValue, $text) !== false);
    }

    /**
     * @param \DOMNode $node
     * @return bool
     */
    public static function nodeStartInsDel($node)
    {
        if (preg_match(static::INS_START_MATCH, $node->nodeValue)) {
            return true;
        }
        if (preg_match(static::DEL_START_MATCH, $node->nodeValue)) {
            return true;
        }
        return false;
    }

    /**
     * @internal
     * @param string $text
     * @return string[]
     */
    public function splitTextByMarkers($text)
    {
        return preg_split('/###(INS|DEL)_(START|END)###/siu', $text);
    }

    /**
     * @internal
     * @param string $param
     * @return \DOMElement
     */
    private function createIns($param)
    {
        $ins = $this->nodeCreator->createElement('ins');
        if ($this->formatting == static::FORMATTING_INLINE) {
            $ins->setAttribute('style', 'color: green; text-decoration: underline;');
        }
        if ($this->insCallback) {
            call_user_func($this->insCallback, $ins, $param);
        }
        return $ins;
    }

    /**
     * @internal
     * @param string $param
     * @return \DOMElement
     */
    private function createDel($param)
    {
        $ins = $this->nodeCreator->createElement('del');
        if ($this->formatting == static::FORMATTING_INLINE) {
            $ins->setAttribute('style', 'color: red; text-decoration: line-through;');
        }
        if ($this->delCallback) {
            call_user_func($this->delCallback, $ins, $param);
        }
        return $ins;
    }

    /**
     * @param \DOMElement $element
     * @param string $param
     */
    private function addInsStyles(\DOMElement $element, $param)
    {
        static::nodeAddClass($element, 'inserted');
        if ($this->formatting == static::FORMATTING_INLINE) {
            $element->setAttribute('style', 'color: green; text-decoration: underline;');
        }
        if ($this->insCallback) {
            call_user_func($this->insCallback, $element, $param);
        }
    }

    /**
     * @param \DOMElement $element
     * @param string $param
     */
    private function addDelStyles(\DOMElement $element, $param)
    {
        static::nodeAddClass($element, 'deleted');
        if ($this->formatting == static::FORMATTING_INLINE) {
            $element->setAttribute('style', 'color: red; text-decoration: line-through;');
        }
        if ($this->delCallback) {
            call_user_func($this->delCallback, $element, $param);
        }
    }

    /**
     * @param \DOMNode $node
     * @return \DOMNode
     */
    private function cloneNode(\DOMNode $node)
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
     * @param $text
     * @param bool $inIns
     * @param bool $inDel
     * @param \DOMText|\DOMElement|null $lastEl
     * @return array
     */
    public function textToNodes($text, $inIns, $inDel, $lastEl)
    {
        $nodes     = [];
        $lastIsIns = ($lastEl && is_a($lastEl, \DOMElement::class) && $lastEl->nodeName == 'ins');
        $lastIsDel = ($lastEl && is_a($lastEl, \DOMElement::class) && $lastEl->nodeName == 'del');
        while ($text != '') {
            if ($inIns !== false) {
                $split = preg_split('/###INS_END###/siu', $text, 2);
                if ($split[0] != '') {
                    $newText = $this->nodeCreator->createTextNode($split[0]);
                    if ($lastIsIns) {
                        $lastEl->appendChild($newText);
                    } else {
                        $newNode = $this->createIns($inIns);
                        $newNode->appendChild($newText);
                        $nodes[] = $newNode;
                    }
                }
                if (count($split) == 2) {
                    $text  = $split[1];
                    $inIns = false;
                } else {
                    $text = '';
                }
            } elseif ($inDel !== false) {
                $split = preg_split('/###DEL_END###/siu', $text, 2);
                if ($split[0] != '') {
                    $newText = $this->nodeCreator->createTextNode($split[0]);
                    if ($lastIsDel) {
                        $lastEl->appendChild($newText);
                    } else {
                        $newNode = $this->createDel($inDel);
                        $newNode->appendChild($newText);
                        $nodes[] = $newNode;
                    }
                }
                if (count($split) == 2) {
                    $text  = $split[1];
                    $inDel = false;
                } else {
                    $text = '';
                }
            } else {
                $split = preg_split('/(###(?:INS|DEL)_START([^#]{0,20})###)/siu', $text, 2, PREG_SPLIT_DELIM_CAPTURE);
                if (count($split) == 4) {
                    if ($split[0] != '') {
                        $newText = $this->nodeCreator->createTextNode($split[0]);
                        $nodes[] = $newText;
                    }
                    $text = $split[3];
                    if (preg_match(static::INS_START_MATCH, $split[1])) {
                        $inIns = $split[2];
                    } elseif (preg_match(static::DEL_START_MATCH, $split[1])) {
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

    /**
     * @param \DOMElement $child
     * @param array $newChildren
     * @param bool $inIns
     * @param bool $inDel
     */
    protected function renderHtmlWithPlaceholdersIntElement(\DOMElement $child, &$newChildren, &$inIns, &$inDel)
    {
        if ($inIns !== false && static::nodeContainsText($child, static::INS_END)) {
            list($currNewChildren, $inIns, $inDel) = $this->renderHtmlWithPlaceholdersIntInIns($child, $inIns);
            $newChildren = array_merge($newChildren, $currNewChildren);
        } elseif ($inDel !== false && static::nodeContainsText($child, static::DEL_END)) {
            list($currNewChildren, $inIns, $inDel) = $this->renderHtmlWithPlaceholdersIntInDel($child, $inDel);
            $newChildren = array_merge($newChildren, $currNewChildren);
        } elseif ($inIns !== false) {
            /** @var \DOMElement $lastEl */
            $lastEl    = (count($newChildren) > 0 ? $newChildren[count($newChildren) - 1] : null);
            $prevIsIns = ($lastEl && is_a($lastEl, \DOMElement::class) && $lastEl->nodeName == 'ins');
            if ($prevIsIns && static::nodeCanBeAttachedToDelIns($child)) {
                $lastEl->appendChild(static::cloneNode($child));
            } elseif (static::nodeCanBeAttachedToDelIns($child)) {
                $delNode = $this->createIns($inIns);
                $delNode->appendChild(static::cloneNode($child));
                $newChildren[] = $delNode;
            } else {
                /** @var \DOMElement $clone */
                $clone = static::cloneNode($child);
                $this->addInsStyles($clone, $inIns);
                $newChildren[] = $clone;
            }
        } elseif ($inDel !== false) {
            /** @var \DOMElement $lastEl */
            $lastEl    = (count($newChildren) > 0 ? $newChildren[count($newChildren) - 1] : null);
            $prevIsDel = ($lastEl && is_a($lastEl, \DOMElement::class) && $lastEl->nodeName == 'del');
            if ($prevIsDel && static::nodeCanBeAttachedToDelIns($child)) {
                $lastEl->appendChild(static::cloneNode($child));
            } elseif (static::nodeCanBeAttachedToDelIns($child)) {
                $delNode = $this->createDel($inDel);
                $delNode->appendChild(static::cloneNode($child));
                $newChildren[] = $delNode;
            } else {
                /** @var \DOMElement $clone */
                $clone = static::cloneNode($child);
                $this->addDelStyles($clone, $inDel);
                $newChildren[] = $clone;
            }
        } else {
            list($currNewChildren, $inIns, $inDel) = $this->renderHtmlWithPlaceholdersIntNormal($child);
            $newChildren = array_merge($newChildren, $currNewChildren);
        }
    }

    /**
     * @param \DOMNode $dom
     * @param bool|string $inIns
     * @return array
     */
    protected function renderHtmlWithPlaceholdersIntInIns($dom, $inIns)
    {
        if (!static::nodeContainsText($dom, static::INS_END)) {
            return [[$this->cloneNode($dom)], $inIns, false];
        }

        $inDel       = false;
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

    /**
     * @param \DOMNode $dom
     * @param bool|string $inDel
     * @return array
     */
    protected function renderHtmlWithPlaceholdersIntInDel($dom, $inDel)
    {
        if (!static::nodeContainsText($dom, static::DEL_END)) {
            return [[$this->cloneNode($dom)], false, $inDel];
        }

        $inIns       = false;
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

    /**
     * @param \DOMElement $dom
     * @return array
     */
    protected function renderHtmlWithPlaceholdersIntNormal($dom)
    {
        if (!static::nodeStartInsDel($dom)) {
            return [[$this->cloneNode($dom)], false, false];
        }
        $inIns       = $inDel = false;
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

    /**
     * @param string $html
     * @return string
     */
    public function renderHtmlWithPlaceholders($html)
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
     * @param string $line
     * @return false|int
     */
    public static function paragraphContainsDiff($line)
    {
        $firstDiffs = [];
        if (preg_match('/(<ins( [^>]*)?>)/siu', $line, $matches, PREG_OFFSET_CAPTURE)) {
            // Workaround: PREG_OFFSET_CAPTURE ignores utf-8
            $pos          = strlen(utf8_decode(substr($line, 0, $matches[0][1])));
            $firstDiffs[] = $pos;
        }
        if (preg_match('/(<del( [^>]*)?>)/siu', $line, $matches, PREG_OFFSET_CAPTURE)) {
            $pos          = strlen(utf8_decode(substr($line, 0, $matches[0][1])));
            $firstDiffs[] = $pos;
        }
        if (preg_match('/(<[^>]+[ "]inserted[ "][^>]*>)/siu', $line, $matches, PREG_OFFSET_CAPTURE)) {
            $pos          = strlen(utf8_decode(substr($line, 0, $matches[0][1])));
            $firstDiffs[] = $pos;
        }
        if (preg_match('/(<[^>]+[ "]deleted[ "][^>]*>)/siu', $line, $matches, PREG_OFFSET_CAPTURE)) {
            $pos          = strlen(utf8_decode(substr($line, 0, $matches[0][1])));
            $firstDiffs[] = $pos;
        }
        if (count($firstDiffs) == 0) {
            return false;
        }
        return min($firstDiffs);
    }

    /**
     * @param string $diff
     * @param Amendment[] $amendmentsById
     * @return string
     */
    public static function renderForInlineDiff($diff, $amendmentsById)
    {
        $renderer = new DiffRenderer();
        $renderer->setInsCallback(function ($node, $params) use ($amendmentsById) {
            /** @var \DOMElement $node */
            $params    = explode('-', $params);
            $amendment = $amendmentsById[$params[1]];
            foreach ($amendment->getInlineChangeData($params[0]) as $key => $val) {
                $node->setAttribute($key, $val);
            }
            $classes = explode(' ', $node->getAttribute('class'));
            $classes = array_merge($classes, ['ice-ins', 'ice-cts', 'appendHint']);
            $node->setAttribute('class', implode(' ', $classes));

        });
        $renderer->setDelCallback(function ($node, $params) use ($amendmentsById) {
            /** @var \DOMElement $node */
            $params    = explode('-', $params);
            $amendment = $amendmentsById[$params[1]];
            foreach ($amendment->getInlineChangeData($params[0]) as $key => $val) {
                $node->setAttribute($key, $val);
            }
            $classes = explode(' ', $node->getAttribute('class'));
            $classes = array_merge($classes, ['ice-del', 'ice-cts', 'appendHint']);
            $node->setAttribute('class', implode(' ', $classes));
        });
        return $renderer->renderHtmlWithPlaceholders($diff);
    }
}
