<?php

namespace app\components\diff;

use app\components\HTMLTools;

class DiffRenderer
{
    const INS_START = '###INS_START###';
    const INS_END   = '###INS_END###';
    const DEL_START = '###DEL_START###';
    const DEL_END   = '###DEL_END###';

    /** @var \DOMDocument */
    private $nodeCreator;

    /**
     */
    public function __construct()
    {
        $this->nodeCreator = new \DOMDocument();
    }

    /**
     * @param \DOMNode $node
     * @param string $text
     * @return bool
     */
    public static function nodeContainsText($node, $text)
    {
        return (mb_strpos($node->nodeValue, $text) !== false);
    }

    /**
     * @param string $text
     * @return string[]
     */
    public function splitTextByMarkers($text)
    {
        return preg_split('/###(INS|DEL)_(START|END)###/siu', $text);
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
            // @TODO Attributes
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
     * @param $text
     * @param bool $inIns
     * @param bool $inDel
     * @return array
     */
    public function textToNodes($text, $inIns, $inDel)
    {
        echo "\n" . $text . "\n\n";
        $nodes = [];
        while ($text != '') {
            if ($inIns) {
                $split = preg_split('/###INS_END###/siu', $text, 2);
                if ($split[0] != '') {
                    $newNode = $this->nodeCreator->createElement('ins');
                    $newText = $this->nodeCreator->createTextNode($split[0]);
                    $newNode->appendChild($newText);
                    $nodes[] = $newNode;
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
                    $newNode = $this->nodeCreator->createElement('del');
                    $newText = $this->nodeCreator->createTextNode($split[0]);
                    $newNode->appendChild($newText);
                    $nodes[] = $newNode;
                }
                if (count($split) == 2) {
                    $text  = $split[1];
                    $inDel = false;
                } else {
                    $text = '';
                }
            } else {
                $split = preg_split('/(###(?:INS|DEL)_START###)/siu', $text, 2, PREG_SPLIT_DELIM_CAPTURE);
                var_dump($split);
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
        }
        return [$nodes, $inIns, $inDel];
    }

    /**
     * @param \DOMNode $dom
     * @return array
     */
    protected function renderHtmlWithPlaceholdersIntInIns($dom)
    {
        return $dom; // @TODO
    }

    /**
     * @param \DOMNode $dom
     * @return array
     */
    protected function renderHtmlWithPlaceholdersIntInDel($dom)
    {
        return $dom; // @TODO
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
            echo "Child: ";
            var_dump($child);
            if (is_a($child, \DOMText::class)) {
                /** @var \DOMText $child */
                echo 'Found TExt: ' . $child->nodeValue;
                list($currNewChildren, $inIns, $inDel) = $this->textToNodes($child->nodeValue, $inIns, $inDel);
                echo ' => ' . count ($currNewChildren) . ' nodes' . "\n";
                $newChildren = array_merge($newChildren, $currNewChildren);
            } elseif (is_a($child, \DOMElement::class)) {
                /** @var \DOMElement $child */
                if ($inIns && static::nodeContainsText($dom, static::INS_END)) {
                    echo 'inIns ' . $child->nodeName . ' ' . "\n";
                } elseif ($inDel && static::nodeContainsText($dom, static::DEL_END)) {
                    echo 'inDel ' . $child->nodeName . ' ' . "\n";
                } else {
                    echo 'Putting ' . $child->nodeName . ' unchanged on newChildren' . "\n";
                    list($currNewChildren, $inIns, $inDel) = $this->renderHtmlWithPlaceholdersIntNormal($child);
                    echo 'Putting ' . $child->nodeName . ' unchanged on newChildren' . " (end)\n";
                    $newChildren = array_merge($newChildren, $currNewChildren);
                }
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
