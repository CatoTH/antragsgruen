<?php

namespace app\components\opendocument;

use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

abstract class Base
{

    const NS_OFFICE   = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    const NS_TEXT     = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    const NS_FO       = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    const NS_STYLE    = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    const NS_TABLE    = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    const NS_CALCTEXT = 'urn:org:documentfoundation:names:experimental:calc:xmlns:calcext:1.0';


    /** @var \DOMDocument */
    protected $doc = null;

    /** @var bool */
    protected $DEBUG = false;

    /**
     * @param string $content
     */
    public function __construct($content)
    {
        $this->doc = new \DOMDocument();
        $this->doc->loadXML($content);
    }

    /***
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->DEBUG = $debug;
    }

    /**
     */
    public function debugOutput()
    {
        $this->doc->preserveWhiteSpace = false;
        $this->doc->formatOutput       = true;
        echo Html::encode($this->doc->saveXML());
        die();
    }

    /**
     * @param string $styleName
     * @param string $family
     * @param string $element
     * @param string[] $attributes
     */
    protected function appendStyleNode($styleName, $family, $element, $attributes)
    {
        $node = $this->doc->createElementNS(static::NS_STYLE, 'style');
        $node->setAttribute('style:name', $styleName);
        $node->setAttribute('style:family', $family);

        $style = $this->doc->createElementNS(static::NS_STYLE, $element);
        foreach ($attributes as $att_name => $att_val) {
            $style->setAttribute($att_name, $att_val);
        }
        $node->appendChild($style);

        foreach ($this->doc->getElementsByTagNameNS(static::NS_OFFICE, 'automatic-styles') as $element) {
            /** @var \DOMElement $element */
            $element->appendChild($node);
        }
    }

    /**
     * @param string $styleName
     * @param array $attributes
     */
    protected function appendTextStyleNode($styleName, $attributes)
    {
        $this->appendStyleNode($styleName, 'text', 'text-properties', $attributes);
    }

    /**
     * @param string $styleName
     * @param array $attributes
     */
    protected function appendRowStyleNode($styleName, $attributes)
    {
        $this->appendStyleNode($styleName, 'table-row', 'table-row-properties', $attributes);
    }

    /**
     * @param string $styleName
     * @param array $attributes
     */
    protected function appendColStyleNode($styleName, $attributes)
    {
        $this->appendStyleNode($styleName, 'table-column', 'table-column-properties', $attributes);
    }

    /**
     * @param string $styleName
     * @param array $cellAttributes
     * @param array $textAttributes
     */
    protected function appendCellStyleNode($styleName, $cellAttributes, $textAttributes)
    {
        $node = $this->doc->createElementNS(static::NS_STYLE, "style");
        $node->setAttribute("style:name", $styleName);
        $node->setAttribute("style:family", 'table-cell');
        $node->setAttribute("style:parent-style-name", "Default");

        if (count($cellAttributes) > 0) {
            $style = $this->doc->createElementNS(static::NS_STYLE, 'table-cell-properties');
            foreach ($cellAttributes as $att_name => $att_val) {
                $style->setAttribute($att_name, $att_val);
            }
            $node->appendChild($style);
        }
        if (count($textAttributes) > 0) {
            $style = $this->doc->createElementNS(static::NS_STYLE, 'text-properties');
            foreach ($textAttributes as $att_name => $att_val) {
                $style->setAttribute($att_name, $att_val);
            }
            $node->appendChild($style);
        }

        foreach ($this->doc->getElementsByTagNameNS(static::NS_OFFICE, 'automatic-styles') as $element) {
            /** @var \DOMElement $element */
            $element->appendChild($node);
        }
    }

    /**
     * @return bool
     */
    protected function noNestedPs()
    {
        return false;
    }

    /**
     * @param \DOMNode $srcNode
     * @param int $templateType
     * @param bool $blockFurtherPs
     * @return \DOMNode
     */
    protected function html2ooNodeInt($srcNode, $templateType, $blockFurtherPs = false)
    {
        switch ($srcNode->nodeType) {
            case XML_ELEMENT_NODE:
                /** @var \DOMElement $srcNode */
                if ($this->DEBUG) {
                    echo "Element - " . $srcNode->nodeName . " / Children: " . count($srcNode->childNodes) . "<br>";
                }
                $append_el = null;
                switch ($srcNode->nodeName) {
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
                        if ($blockFurtherPs) {
                            $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'span');
                        } else {
                            $dst_el = $this->doc->createElementNS(static::NS_TEXT, 'p');
                        }
                        break;
                    case 'ul':
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
                if ($this->noNestedPs()) {
                    $blockFurtherPs = true;
                }
                foreach ($srcNode->childNodes as $child) {
                    /** @var \DOMNode $child */
                    if ($this->DEBUG) {
                        echo "CHILD<br>" . $child->nodeType . "<br>";
                    }

                    $dst_node = $this->html2ooNodeInt($child, $templateType, $blockFurtherPs);
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

        $html = HtmlPurifier::process(
            $html,
            [
                'HTML.Doctype' => 'HTML 4.01 Transitional',
                'HTML.Trusted' => true,
                'CSS.Trusted'  => true,
            ]
        );

        $src_doc = new \DOMDocument();
        $src_doc->loadHTML('<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head><body>' . $html . "</body></html>");
        $bodies = $src_doc->getElementsByTagName('body');
        $body   = $bodies->item(0);

        $new_nodes = [];
        for ($i = 0; $i < $body->childNodes->length; $i++) {
            $child = $body->childNodes->item($i);

            /** @var \DOMNode $child */
            if ($child->nodeName == 'ul') {
                // Alle anderen Nocdes dieses Aufrufs werden ignoriert
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
     * @param int
     * @throws \Exception
     * @return \DOMNode
     */
    abstract protected function getNextNodeTemplate($templateType);
}
