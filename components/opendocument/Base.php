<?php

namespace app\components\opendocument;

use yii\helpers\Html;

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
}
