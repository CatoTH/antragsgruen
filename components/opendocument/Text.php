<?php

namespace app\components\opendocument;

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
