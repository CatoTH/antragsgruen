<?php

namespace app\components\opendocument;

use app\components\HTMLTools;
use yii\helpers\Html;

class Text extends Base
{
    const TEMPLATE_TYPE_ANTRAG      = 0;
    const TEMPLATE_TYPE_BEGRUENDUNG = 1;

    /** @var null|\DOMElement */
    private $nodeTemplate1 = null;
    /** @var null|\DOMElement */
    private $nodeTemplateN = null;
    /** @var null|\DOMElement */
    private $nodeReason = null;

    /** @var bool */
    private $node_template_1_used = false;

    private $replaces = [];


    /**
     * @param string $search
     * @param string $replace
     */
    public function addReplace($search, $replace)
    {
        $this->replaces[$search] = $replace;
    }

    /**
     * @param \DOMNode $srcNode
     * @param int $templateType
     * @return \DOMNode
     */
    protected function html2ooNodeInt($srcNode, $templateType)
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
                        // @TODO Formattings
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
                    case 'u':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'span');
                        $dst_el->setAttribute('text:style-name', 'AntragsgruenUnderlined');
                        break;
                    case 'br':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'line-break');
                        break;
                    case 'p':
                    case 'div':
                    case 'blockquote':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'p');
                        break;
                    case 'ul':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'list');
                        break;
                    case 'ol':
                        $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'list');
                        break;
                    case 'li':
                        $dst_el    = $this->doc->createElementNS(static::NS_TEXT, 'list-item');
                        $append_el = $this->getNextNodeTemplate($templateType);
                        $dst_el->appendChild($append_el);
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
                    default:
                        die('Unknown Tag: ' . $srcNode->nodeName);
                }
                foreach ($srcNode->childNodes as $child) {
                    /** @var \DOMNode $child */
                    if ($this->DEBUG) {
                        echo "CHILD<br>" . $child->nodeType . "<br>";
                    }

                    $dst_node = $this->html2ooNodeInt($child, $templateType);
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
     * @param int $templateType
     * @return \DOMNode[]
     */
    protected function html2ooNodes($html, $templateType)
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
                $new_node = $this->html2ooNodeInt($child, $templateType);
            } else {
                if ($child->nodeType == XML_TEXT_NODE) {
                    $new_node = $this->getNextNodeTemplate($templateType);
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
                    $new_node = $this->html2ooNodeInt($child, $templateType);
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
    public function convert($motionParagraphs, $reason)
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
        $this->appendTextStyleNode('AntragsgruenIns', [
            'fo:color' => '#00ff00',
        ]);
        $this->appendTextStyleNode('AntragsgruenDel', [
            'fo:color' => '#ff0000',
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

                    if (preg_match("/\{\{ANTRAGSGRUEN:BEGRUENDUNG( [^\}]*)?/siu", $child->data)) {
                        $this->nodeReason = $node;
                    }
                    if (preg_match("/\{\{ANTRAGSGRUEN:ANTRAGSTEXT_1( [^\}]*)?/siu", $child->data)) {
                        $this->nodeTemplate1 = $node;
                    }
                    if (preg_match("/\{\{ANTRAGSGRUEN:ANTRAGSTEXT_N( [^\}]*)?/siu", $child->data)) {
                        $this->nodeTemplateN = $node;
                    }
                }
            }
        }

        if ($this->nodeReason) {
            if ($reason) {
                $new_nodes = $this->html2ooNodes($reason, static::TEMPLATE_TYPE_BEGRUENDUNG);
                foreach ($new_nodes as $new_node) {
                    $this->nodeReason->parentNode->insertBefore($new_node, $this->nodeReason);
                }
            }
            $this->nodeReason->parentNode->removeChild($this->nodeReason);
        }

        if ($this->nodeTemplate1) {
            $html = HtmlBBcodeUtils::bbcode2html($motionParagraphs[0]->str_bbcode);
            if ($this->DEBUG) {
                echo "======<br>" . nl2br(Html::encode($html)) . "<br>========<br>";
            }
            $new_nodes = $this->html2ooNodes($html, static::TEMPLATE_TYPE_ANTRAG);
            foreach ($new_nodes as $new_node) {
                $this->nodeTemplate1->parentNode->insertBefore($new_node, $this->nodeTemplate1);
            }
            $this->nodeTemplate1->parentNode->removeChild($this->nodeTemplate1);
        }
        if ($this->nodeTemplateN) {
            for ($i = 1; $i < count($motionParagraphs); $i++) {
                $html = HtmlBBcodeUtils::bbcode2html($motionParagraphs[$i]->str_bbcode);
                if ($this->DEBUG) {
                    echo "======<br>" . nl2br(Html::encode($html)) . "<br>========<br>";
                }
                $new_nodes = $this->html2ooNodes($html, static::TEMPLATE_TYPE_ANTRAG);
                foreach ($new_nodes as $new_node) {
                    $this->nodeTemplateN->parentNode->insertBefore($new_node, $this->nodeTemplateN);
                }
            }
            $this->nodeTemplateN->parentNode->removeChild($this->nodeTemplateN);
        }


        return $this->doc->saveXML();
    }

    /**
     * @param int
     * @throws \Exception
     * @return \DOMNode
     */
    protected function getNextNodeTemplate($template_type)
    {
        if ($template_type == static::TEMPLATE_TYPE_BEGRUENDUNG) {
            return $this->nodeReason->cloneNode();
        }
        if ($template_type == static::TEMPLATE_TYPE_ANTRAG) {
            if ($this->node_template_1_used && $this->nodeTemplateN) {
                return $this->nodeTemplateN->cloneNode();
            } else {
                $this->node_template_1_used = true;
                return $this->nodeTemplate1->cloneNode();
            }
        }
        throw new \Exception("Ungültiges Template");
    }
}
