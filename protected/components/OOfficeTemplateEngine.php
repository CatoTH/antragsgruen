<?php


class OOfficeTemplateEngine
{

    public static $ODT_NS_OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    public static $ODT_NS_TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    public static $ODT_NS_FO = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    public static $ODT_NS_STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';


    /** @var DOMDocument */
    private $doc = null;

    /** @var bool */
    protected $DEBUG = false;

    /***
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->DEBUG = $debug;
    }

    public function debugOutput()
    {
        $this->doc->preserveWhiteSpace = false;
        $this->doc->formatOutput       = true;
        echo CHtml::encode($this->doc->saveXML());
        die();
    }

    /**
     * @param string $style_name
     * @param array $attributes
     */
    protected function appendStyleNode($style_name, $attributes)
    {
        $node = $this->doc->createElementNS(static::$ODT_NS_STYLE, "style");
        $node->setAttribute("style:name", $style_name);
        $node->setAttribute("style:family", "text");

        $style = $this->doc->createElementNS(static::$ODT_NS_STYLE, "text-properties");
        foreach ($attributes as $att_name => $att_val) {
            $style->setAttribute($att_name, $att_val);
        }
        $node->appendChild($style);

        foreach ($this->doc->getElementsByTagNameNS(static::$ODT_NS_OFFICE, 'automatic-styles') as $element) {
            /** @var DOMElement $element */
            $element->appendChild($node);
        }
    }

    /**
     * @param DOMNode $src_node
     * @param int $template_type
     * @return \DOMText|null
     */
    protected function html2odtNode_int($src_node, $template_type)
    {
        switch ($src_node->nodeType) {
            case XML_ELEMENT_NODE:
                /** @var DOMElement $src_node */
                if ($this->DEBUG) echo "Element - " . $src_node->nodeName . " / Children: " . count($src_node->childNodes) . "<br>";
                $append_el = null;
                switch ($src_node->nodeName) {
                    case "b":
                        $dst_el = $this->doc->createElementNS(static::$ODT_NS_TEXT, "span");
                        $dst_el->setAttribute("text:style-name", "Antragsgruen_fett");
                        break;
                    case "i":
                        $dst_el = $this->doc->createElementNS(static::$ODT_NS_TEXT, "span");
                        $dst_el->setAttribute("text:style-name", "Antragsgruen_kursiv");
                        break;
                    case "u":
                        $dst_el = $this->doc->createElementNS(static::$ODT_NS_TEXT, "span");
                        $dst_el->setAttribute("text:style-name", "Antragsgruen_unterstrichen");
                        break;
                    case "br":
                        $dst_el = $this->doc->createElementNS(static::$ODT_NS_TEXT, "line-break");
                        break;
                    case "ul":
                        $dst_el = $this->doc->createElementNS(static::$ODT_NS_TEXT, "list");
                        break;
                    case "li":
                        $dst_el    = $this->doc->createElementNS(static::$ODT_NS_TEXT, "list-item");
                        $append_el = $this->getNextNodeTemplate($template_type);
                        $dst_el->appendChild($append_el);
                        break;
                    default:
                        die("Unbekanntes Tag: " . $src_node->nodeName);
                }
                foreach ($src_node->childNodes as $child) {
                    /** @var DOMNode $child */
                    if ($this->DEBUG) echo $child->nodeType . "<br>";
                    $dst_node = $this->html2odtNode_int($child, $template_type);
                    if ($this->DEBUG) var_dump($dst_node);
                    if ($dst_node) {
                        if ($append_el) $append_el->appendChild($dst_node);
                        else $dst_el->appendChild($dst_node);
                    }
                }
                return $dst_el;
                break;
            case XML_TEXT_NODE:
                /** @var DOMText $src_node */
                $textnode       = new DOMText();
                $textnode->data = $src_node->data;
                if ($this->DEBUG) echo "Text<br>";
                return $textnode;
                break;
            case XML_DOCUMENT_TYPE_NODE:
                if ($this->DEBUG) echo "Type Node<br>";
                return null;
                break;
            default:
                if ($this->DEBUG) echo "Unknown Node: " . $src_node->nodeType . "<br>";
                return null;
        }
    }

    /**
     * @param string $html
     * @param int $template_type
     * @return DOMNode
     */
    protected function html2odtNode($html, $template_type)
    {

        $src_doc = new DOMDocument();
        $src_doc->loadHTML('<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head><body>' . $html . "</body></html>");
        $bodies = $src_doc->getElementsByTagName("body");
        $body   = $bodies->item(0);

        $p = null;
        if (count($body->childNodes) == 1) {
            foreach ($body->childNodes as $child) {
                if ($child->nodeName == "p") $body = $child;
            }
        }

        $new_node = $this->getNextNodeTemplate($template_type);
        foreach ($body->childNodes as $child) {
            /** @var DOMNode $child */
            if ($child->nodeName == "ul") {
                // Alle anderen Nocdes dieses Aufrufs werden ignoriert
                if ($this->DEBUG) echo "LIST<br>";
                $dst_node = $this->html2odtNode_int($child, $template_type);
                return $dst_node;
            } else {
                if ($this->DEBUG) echo $child->nodeName . "!!!!!!!!!!!!<br>";
                $dst_node = $this->html2odtNode_int($child, $template_type);
                if ($dst_node) $new_node->appendChild($dst_node);
            }
        }
        return $new_node;
    }

    /**
     * @param int
     * @throws \Exception
     * @return DOMNode
     */
    protected function getNextNodeTemplate($template_type)
    {
        $dom = new DOMNode();
        return $dom;
    }

}

