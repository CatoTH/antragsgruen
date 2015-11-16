<?php

namespace app\components\diff;

use app\components\HTMLTools;

class DiffRenderer
{
    const FORMATTING_CLASSES = 0;
    const FORMATTING_INLINE  = 1;

    const INS_START = '###INS_START###';
    const INS_END   = '###INS_END###';
    const DEL_START = '###DEL_START###';
    const DEL_END   = '###DEL_END###';

    /** @var \DOMDocument */
    private $nodeCreator;

    /** @var int */
    private $formatting = 0;

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
     * @internal
     * @param string $text
     * @return string[]
     */
    public function splitTextByMarkers($text)
    {
        return preg_split('/###(INS|DEL)_(START|END)###/siu', $text);
    }

    /**
     * @return \DOMElement
     */
    private function createIns()
    {
        $ins = $this->nodeCreator->createElement('ins');
        if ($this->formatting == static::FORMATTING_INLINE) {
            $ins->setAttribute('style', 'color: green; text-decoration: underline;');
        }
        return $ins;
    }

    /**
     * @return \DOMElement
     */
    private function createDel()
    {
        $ins = $this->nodeCreator->createElement('del');
        if ($this->formatting == static::FORMATTING_INLINE) {
            $ins->setAttribute('style', 'color: red; text-decoration: line-through;');
        }
        return $ins;
    }

    /**
     * @param \DOMElement $element
     */
    private function addInsStyles(\DOMElement $element)
    {
        static::nodeAddClass($element, 'inserted');
        if ($this->formatting == static::FORMATTING_INLINE) {
            $element->setAttribute('style', 'color: green; text-decoration: underline;');
        }
    }

    /**
     * @param \DOMElement $element
     */
    private function addDelStyles(\DOMElement $element)
    {
        static::nodeAddClass($element, 'deleted');
        if ($this->formatting == static::FORMATTING_INLINE) {
            $element->setAttribute('style', 'color: red; text-decoration: line-through;');
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
            if ($inIns) {
                $split = preg_split('/###INS_END###/siu', $text, 2);
                if ($split[0] != '') {
                    $newText = $this->nodeCreator->createTextNode($split[0]);
                    if ($lastIsIns) {
                        $lastEl->appendChild($newText);
                    } else {
                        $newNode = $this->createIns();
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
            } elseif ($inDel) {
                $split = preg_split('/###DEL_END###/siu', $text, 2);
                if ($split[0] != '') {
                    $newText = $this->nodeCreator->createTextNode($split[0]);
                    if ($lastIsDel) {
                        $lastEl->appendChild($newText);
                    } else {
                        $newNode = $this->createDel();
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
                $split = preg_split('/(###(?:INS|DEL)_START###)/siu', $text, 2, PREG_SPLIT_DELIM_CAPTURE);
                if (count($split) == 3) {
                    if ($split[0] != '') {
                        $newText = $this->nodeCreator->createTextNode($split[0]);
                        $nodes[] = $newText;
                    }
                    $text = $split[2];
                    if ($split[1] == '###INS_START###') {
                        $inIns = true;
                    } elseif ($split[1] == '###DEL_START###') {
                        $inDel = true;
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
        if ($inIns && static::nodeContainsText($child, static::INS_END)) {
            list($currNewChildren, $inIns, $inDel) = $this->renderHtmlWithPlaceholdersIntInIns($child);
            $newChildren = array_merge($newChildren, $currNewChildren);
        } elseif ($inDel && static::nodeContainsText($child, static::DEL_END)) {
            list($currNewChildren, $inIns, $inDel) = $this->renderHtmlWithPlaceholdersIntInDel($child);
            $newChildren = array_merge($newChildren, $currNewChildren);
        } elseif ($inIns) {
            /** @var \DOMElement $lastEl */
            $lastEl    = (count($newChildren) > 0 ? $newChildren[count($newChildren) - 1] : null);
            $prevIsIns = ($lastEl && is_a($lastEl, \DOMElement::class) && $lastEl->nodeName == 'ins');
            if ($prevIsIns && static::nodeCanBeAttachedToDelIns($child)) {
                $lastEl->appendChild(static::cloneNode($child));
            } elseif (static::nodeCanBeAttachedToDelIns($child)) {
                $delNode = $this->createIns();
                $delNode->appendChild(static::cloneNode($child));
                $newChildren[] = $delNode;
            } else {
                /** @var \DOMElement $clone */
                $clone = static::cloneNode($child);
                $this->addInsStyles($clone);
                $newChildren[] = $clone;
            }
        } elseif ($inDel) {
            /** @var \DOMElement $lastEl */
            $lastEl    = (count($newChildren) > 0 ? $newChildren[count($newChildren) - 1] : null);
            $prevIsDel = ($lastEl && is_a($lastEl, \DOMElement::class) && $lastEl->nodeName == 'del');
            if ($prevIsDel && static::nodeCanBeAttachedToDelIns($child)) {
                $lastEl->appendChild(static::cloneNode($child));
            } elseif (static::nodeCanBeAttachedToDelIns($child)) {
                $delNode = $this->createDel();
                $delNode->appendChild(static::cloneNode($child));
                $newChildren[] = $delNode;
            } else {
                /** @var \DOMElement $clone */
                $clone = static::cloneNode($child);
                $this->addDelStyles($clone);
                $newChildren[] = $clone;
            }
        } else {
            list($currNewChildren, $inIns, $inDel) = $this->renderHtmlWithPlaceholdersIntNormal($child);
            $newChildren = array_merge($newChildren, $currNewChildren);
        }
    }

    /**
     * @param \DOMNode $dom
     * @return array
     */
    protected function renderHtmlWithPlaceholdersIntInIns($dom)
    {
        if (!static::nodeContainsText($dom, static::INS_END)) {
            return [[$this->cloneNode($dom)], true, false];
        }

        $inIns       = true;
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
     * @return array
     */
    protected function renderHtmlWithPlaceholdersIntInDel($dom)
    {
        if (!static::nodeContainsText($dom, static::DEL_END)) {
            return [[$this->cloneNode($dom)], false, true];
        }

        $inIns       = false;
        $inDel       = true;
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
     * @return array
     */
    protected function renderHtmlWithPlaceholdersIntNormal($dom)
    {
        if (!static::nodeContainsText($dom, static::INS_START) && !static::nodeContainsText($dom, static::DEL_START)) {
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
}
