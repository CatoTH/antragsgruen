<?php


class OdtTemplateEngine extends OOfficeTemplateEngine
{
    public static $TEMPLATE_TYPE_ANTRAG      = 0;
    public static $TEMPLATE_TYPE_BEGRUENDUNG = 1;

    /** @var null|DOMElement */
    private $node_template_1 = null;
    /** @var null|DOMElement */
    private $node_template_n = null;
    /** @var null|DOMElement */
    private $node_begruendung = null;

    /** @var bool */
    private $node_template_1_used = false;

    private $replaces = array();


    /**
     * @param string $search
     * @param string $replace
     */
    public function addReplace($search, $replace)
    {
        $this->replaces[$search] = $replace;
    }

    /**
     * @param string []
     * @param string
     * @return string
     */
    public function convert($antrag_absaetze, $begruendung)
    {
        $this->appendTextStyleNode("Antragsgruen_fett", array(
            "fo:font-weight"            => "bold",
            "style:font-weight-asian"   => "bold",
            "style:font-weight-complex" => "bold",
        ));
        $this->appendTextStyleNode("Antragsgruen_kursiv", array(
            "fo:font-style"            => "italic",
            "style:font-style-asian"   => "italic",
            "style:font-style-complex" => "italic",
        ));
        $this->appendTextStyleNode("Antragsgruen_unterstrichen", array(
            "style:text-underline-width" => "auto",
            "style:text-underline-color" => "font-color",
            "style:text-underline-style" => "solid",
        ));
        $this->appendTextStyleNode("Antragsgruen_gruen", array(
            "fo:color" => "#00ff00",
        ));
        $this->appendTextStyleNode("Antragsgruen_rot", array(
            "fo:color" => "#ff0000",
        ));

        /** @var DOMNode[] $nodes */
        $nodes = array();
        foreach ($this->doc->getElementsByTagNameNS(static::$NS_TEXT, 'span') as $element) $nodes[] = $element;
        foreach ($this->doc->getElementsByTagNameNS(static::$NS_TEXT, 'p') as $element) $nodes[] = $element;


        foreach ($nodes as $node) {
            $children = $node->childNodes;
            foreach ($children as $child) if ($child->nodeType == XML_TEXT_NODE) {
                /** @var DOMText $child */
                $child->data = preg_replace(array_keys($this->replaces), array_values($this->replaces), $child->data);

                if (preg_match("/\{\{ANTRAGSGRUEN:BEGRUENDUNG( [^\}]*)?/siu", $child->data)) $this->node_begruendung = $node;
                if (preg_match("/\{\{ANTRAGSGRUEN:ANTRAGSTEXT_1( [^\}]*)?/siu", $child->data)) $this->node_template_1 = $node;
                if (preg_match("/\{\{ANTRAGSGRUEN:ANTRAGSTEXT_N( [^\}]*)?/siu", $child->data)) $this->node_template_n = $node;
            }
        }

        if ($this->node_begruendung) {
            if ($begruendung) {
                $new_nodes = $this->html2ooNodes($begruendung, static::$TEMPLATE_TYPE_BEGRUENDUNG);
                foreach ($new_nodes as $new_node) $this->node_begruendung->parentNode->insertBefore($new_node, $this->node_begruendung);
            }
            $this->node_begruendung->parentNode->removeChild($this->node_begruendung);
        }

        if ($this->node_template_1) {
            $html = HtmlBBcodeUtils::bbcode2html($antrag_absaetze[0]->str_bbcode);
            if ($this->DEBUG) echo "======<br>" . nl2br(CHtml::encode($html)) . "<br>========<br>";
            $new_nodes = $this->html2ooNodes($html, static::$TEMPLATE_TYPE_ANTRAG);
            foreach ($new_nodes as $new_node) $this->node_template_1->parentNode->insertBefore($new_node, $this->node_template_1);
            $this->node_template_1->parentNode->removeChild($this->node_template_1);
        }
        if ($this->node_template_n) {
            for ($i = 1; $i < count($antrag_absaetze); $i++) {
                $html = HtmlBBcodeUtils::bbcode2html($antrag_absaetze[$i]->str_bbcode);
                if ($this->DEBUG) echo "======<br>" . nl2br(CHtml::encode($html)) . "<br>========<br>";
                $new_nodes = $this->html2ooNodes($html, static::$TEMPLATE_TYPE_ANTRAG);
                foreach ($new_nodes as $new_node) $this->node_template_n->parentNode->insertBefore($new_node, $this->node_template_n);
            }
            $this->node_template_n->parentNode->removeChild($this->node_template_n);
        }


        return $this->doc->saveXML();
    }

    /**
     * @param int
     * @throws \Exception
     * @return DOMNode
     */
    protected function getNextNodeTemplate($template_type)
    {
        if ($template_type == static::$TEMPLATE_TYPE_BEGRUENDUNG) {
            return $this->node_begruendung->cloneNode();
        }
        if ($template_type == static::$TEMPLATE_TYPE_ANTRAG) {
            if ($this->node_template_1_used && $this->node_template_n) {
                return $this->node_template_n->cloneNode();
            } else {
                $this->node_template_1_used = true;
                return $this->node_template_1->cloneNode();
            }
        }
        throw new Exception("Ungültiges Template");
    }

}

