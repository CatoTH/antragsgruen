<?php

namespace app\components\opendocument;

use app\components\HTMLTools;
use app\models\exceptions\Internal;
use yii\helpers\Html;

class Text extends Base
{
    /** @var null|\DOMElement */
    private $nodeText = null;

    /** @var bool */
    private $node_template_1_used = false;

    /** @var string[] */
    private $replaces = [];

    /** @var array */
    private $textBlocks = [];


    /**
     * @param string $search
     * @param string $replace
     */
    public function addReplace($search, $replace)
    {
        $this->replaces[$search] = $replace;
    }

    /**
     * @param string $html
     * @param bool $lineNumbered
     */
    public function addHtmlTextBlock($html, $lineNumbered)
    {
        $this->textBlocks[] = ['text' => $html, 'lineNumbered' => $lineNumbered];
    }

    /**
     * @param \DOMNode $srcNode
     * @param bool $lineNumbered
     * @return \DOMNode
     */
    protected function html2ooNodeInt($srcNode, $lineNumbered)
    {
        switch ($srcNode->nodeType) {
            case XML_ELEMENT_NODE:
                /** @var \DOMElement $srcNode */
                if ($this->DEBUG) {
                    echo "Element - " . $srcNode->nodeName . " / Children: " . count($srcNode->childNodes) . "<br>";
                }
                $append_el = null;
                switch ($srcNode->nodeName) {
                    case 'span':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'span');
                        if ($srcNode->hasAttribute('class')) {
                            $classes = explode(' ', $srcNode->getAttribute('class'));
                            if (in_array('underline', $classes)) {
                                $dst_el->setAttribute('text:style-name', 'AntragsgruenUnderlined');
                            }
                            if (in_array('strike', $classes)) {
                                $dst_el->setAttribute('text:style-name', 'AntragsgruenStrike');
                            }
                            if (in_array('ins', $classes)) {
                                $dst_el->setAttribute('text:style-name', 'AntragsgruenIns');
                            }
                            if (in_array('inserted', $classes)) {
                                $dst_el->setAttribute('text:style-name', 'AntragsgruenIns');
                            }
                            if (in_array('del', $classes)) {
                                $dst_el->setAttribute('text:style-name', 'AntragsgruenDel');
                            }
                            if (in_array('deleted', $classes)) {
                                $dst_el->setAttribute('text:style-name', 'AntragsgruenDel');
                            }
                            if (in_array('superscript', $classes)) {
                                $dst_el->setAttribute('text:style-name', 'AntragsgruenSup');
                            }
                            if (in_array('subscritp', $classes)) {
                                $dst_el->setAttribute('text:style-name', 'AntragsgruenSub');
                            }
                        }
                        break;
                    case 'b':
                    case 'strong':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'span');
                        $dst_el->setAttribute('text:style-name', 'AntragsgruenBold');
                        break;
                    case 'i':
                    case 'em':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'span');
                        $dst_el->setAttribute('text:style-name', 'AntragsgruenItalic');
                        break;
                    case 's':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'span');
                        $dst_el->setAttribute('text:style-name', 'AntragsgruenStrike');
                        break;
                    case 'u':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'span');
                        $dst_el->setAttribute('text:style-name', 'AntragsgruenUnderlined');
                        break;
                    case 'sub':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'span');
                        $dst_el->setAttribute('text:style-name', 'AntragsgruenSub');
                        break;
                    case 'sup':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'span');
                        $dst_el->setAttribute('text:style-name', 'AntragsgruenSup');
                        break;
                    case 'br':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'line-break');
                        break;
                    case 'del':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'span');
                        $dst_el->setAttribute('text:style-name', 'AntragsgruenDel');
                        break;
                    case 'ins':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'span');
                        $dst_el->setAttribute('text:style-name', 'AntragsgruenIns');
                        break;
                    case 'a':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'a');
                        try {
                            $attr = $srcNode->getAttribute('href');
                            if ($attr) {
                                $dst_el->setAttribute('xlink:href', $attr);
                            }
                        } catch (\Exception $e) {
                        }
                        break;
                    case 'p':
                    case 'div':
                    case 'blockquote':
                        $dst_el = $this->createNodeWithBaseStyle('p', $lineNumbered);
                        break;
                    case 'ul':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'list');
                        break;
                    case 'ol':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'list');
                        break;
                    case 'li':
                        $dst_el    = $this->doc->createElementNS(static::NS_TEXT, 'list-item');
                        $append_el = $this->getNextNodeTemplate($lineNumbered);
                        $dst_el->appendChild($append_el);
                        break;
                    case 'h1':
                        $dst_el = $this->createNodeWithBaseStyle('p', $lineNumbered);
                        $dst_el->setAttribute('text:style-name', 'Antragsgrün_20_H1');
                        break;
                    case 'h2':
                    case 'h3':
                    case 'h4':
                        $dst_el = $this->createNodeWithBaseStyle('p', $lineNumbered);
                        $dst_el->setAttribute('text:style-name', 'Antragsgrün_20_H2');
                        break;
                    default:
                        throw new Internal('Unknown Tag: ' . $srcNode->nodeName);
                }
                foreach ($srcNode->childNodes as $child) {
                    /** @var \DOMNode $child */
                    if ($this->DEBUG) {
                        echo "CHILD<br>" . $child->nodeType . "<br>";
                    }

                    $dst_node = $this->html2ooNodeInt($child, $lineNumbered);
                    if ($this->DEBUG) {
                        echo "CHILD";
                        var_dump($dst_node);
                    }
                    if ($dst_node) {
                        if ($append_el) {
                            $append_el->appendChild($dst_node);
                        } else {
                            $dst_el->appendChild($dst_node);
                        }
                    }
                }
                return $dst_el;
                break;
            case XML_TEXT_NODE:
                /** @var \DOMText $srcNode */
                $textnode       = new \DOMText();
                $textnode->data = $srcNode->data;
                if ($this->DEBUG) {
                    echo 'Text<br>';
                }
                return $textnode;
                break;
            case XML_DOCUMENT_TYPE_NODE:
                if ($this->DEBUG) {
                    echo 'Type Node<br>';
                }
                return null;
                break;
            default:
                if ($this->DEBUG) {
                    echo 'Unknown Node: ' . $srcNode->nodeType . '<br>';
                }
                return null;
        }
    }

    /**
     * @param string $html
     * @param bool $lineNumbered
     * @return \DOMNode[]
     */
    protected function html2ooNodes($html, $lineNumbered)
    {
        $body = HTMLTools::html2DOM($html);

        $new_nodes = [];
        for ($i = 0; $i < $body->childNodes->length; $i++) {
            $child = $body->childNodes->item($i);

            /** @var \DOMNode $child */
            if ($child->nodeName == 'ul') {
                // Alle anderen Nodes dieses Aufrufs werden ignoriert
                if ($this->DEBUG) {
                    echo 'LIST<br>';
                }
                $new_node = $this->html2ooNodeInt($child, $lineNumbered);
            } else {
                if ($child->nodeType == XML_TEXT_NODE) {
                    $new_node = $this->getNextNodeTemplate($lineNumbered);
                    /** @var \DOMText $child */
                    if ($this->DEBUG) {
                        echo $child->nodeName . ' - ' . Html::encode($child->data) . '!!!!!!!!!!!!<br>';
                    }
                    $text       = new \DOMText();
                    $text->data = $child->data;
                    $new_node->appendChild($text);
                } else {
                    if ($this->DEBUG) {
                        echo $child->nodeName . '!!!!!!!!!!!!<br>';
                    }
                    $new_node = $this->html2ooNodeInt($child, $lineNumbered);
                }
            }
            if ($new_node) {
                $new_nodes[] = $new_node;
            }
        }
        return $new_nodes;
    }

    /**
     * @param string[] $motionParagraphs
     * @param string $reason
     * @return string
     */
    public function convert()
    {
        $this->appendTextStyleNode('AntragsgruenBold', [
            'fo:font-weight'            => 'bold',
            'style:font-weight-asian'   => 'bold',
            'style:font-weight-complex' => 'bold',
        ]);
        $this->appendTextStyleNode('AntragsgruenItalic', [
            'fo:font-style'            => 'italic',
            'style:font-style-asian'   => 'italic',
            'style:font-style-complex' => 'italic',
        ]);
        $this->appendTextStyleNode('AntragsgruenUnderlined', [
            'style:text-underline-width' => 'auto',
            'style:text-underline-color' => 'font-color',
            'style:text-underline-style' => 'solid',
        ]);
        $this->appendTextStyleNode('AntragsgruenStrike', [
            'style:text-line-through-style' => 'solid',
            'style:text-line-through-type'  => 'single',
        ]);
        $this->appendTextStyleNode('AntragsgruenIns', [
            'fo:color' => '#00ff00',
        ]);
        $this->appendTextStyleNode('AntragsgruenDel', [
            'fo:color' => '#ff0000',
        ]);
        $this->appendTextStyleNode('AntragsgruenSub', [
            'style:text-position' => 'sub 58%',
        ]);
        $this->appendTextStyleNode('AntragsgruenSup', [
            'style:text-position' => 'super 58%',
        ]);

        /** @var \DOMNode[] $nodes */
        $nodes = [];
        foreach ($this->doc->getElementsByTagNameNS(static::NS_TEXT, 'span') as $element) {
            $nodes[] = $element;
        }
        foreach ($this->doc->getElementsByTagNameNS(static::NS_TEXT, 'p') as $element) {
            $nodes[] = $element;
        }


        $searchFor   = array_keys($this->replaces);
        $replaceWith = array_values($this->replaces);
        foreach ($nodes as $node) {
            $children = $node->childNodes;
            foreach ($children as $child) {
                if ($child->nodeType == XML_TEXT_NODE) {
                    /** @var \DOMText $child */
                    $child->data = preg_replace($searchFor, $replaceWith, $child->data);

                    if (preg_match("/\{\{ANTRAGSGRUEN:DUMMY\}\}/siu", $child->data)) {
                        $node->parentNode->removeChild($node);
                    }
                    if (preg_match("/\{\{ANTRAGSGRUEN:TEXT\}\}/siu", $child->data)) {
                        $this->nodeText = $node;
                    }
                }
            }
        }

        foreach ($this->textBlocks as $textBlock) {
            $newNodes = $this->html2ooNodes($textBlock['text'], $textBlock['lineNumbered']);
            foreach ($newNodes as $newNode) {
                $this->nodeText->parentNode->insertBefore($newNode, $this->nodeText);
            }
        }

        $this->nodeText->parentNode->removeChild($this->nodeText);

        return $this->doc->saveXML();
    }

    /**
     * @param bool $lineNumbers
     * @return \DOMNode
     */
    protected function getNextNodeTemplate($lineNumbers)
    {
        $node = $this->nodeText->cloneNode();
        /** @var \DOMElement $node */
        if ($lineNumbers) {
            if ($this->node_template_1_used) {
                $node->setAttribute('text:style-name', 'Antragsgrün_20_LineNumbered_20_Standard');
            } else {
                $this->node_template_1_used = true;
                $node->setAttribute('text:style-name', 'Antragsgrün_20_LineNumbered_20_First');
            }
        } else {
            $node->setAttribute('text:style-name', 'Antragsgrün_20_Standard');
            return $this->nodeText->cloneNode();
        }
        return $node;
    }

    /**
     * @param string $nodeType
     * @param bool $lineNumbers
     * @return \DOMElement|\DOMNode
     */
    protected function createNodeWithBaseStyle($nodeType, $lineNumbers)
    {
        $node = $this->doc->createElementNS(static::NS_TEXT, $nodeType);
        if ($lineNumbers) {
            if ($this->node_template_1_used) {
                $node->setAttribute('text:style-name', 'Antragsgrün_20_LineNumbered_20_Standard');
            } else {
                $this->node_template_1_used = true;
                $node->setAttribute('text:style-name', 'Antragsgrün_20_LineNumbered_20_First');
            }
        } else {
            $node->setAttribute('text:style-name', 'Antragsgrün_20_Standard');
        }
        return $node;
    }
}
