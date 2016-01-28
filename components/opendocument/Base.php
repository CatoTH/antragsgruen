<?php

namespace app\components\opendocument;

abstract class Base
{
    const NS_OFFICE   = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    const NS_TEXT     = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    const NS_FO       = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    const NS_STYLE    = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    const NS_TABLE    = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    const NS_CALCTEXT = 'urn:org:documentfoundation:names:experimental:calc:xmlns:calcext:1.0';
    const NS_XLINK    = 'http://www.w3.org/1999/xlink';


    /** @var \DOMDocument */
    protected $doc = null;

    /** @var bool */
    protected $DEBUG = false;

    /** @var string */
    protected $tmpPath = '/tmp/';

    /**
     * @param string $content
     */
    public function __construct($content)
    {
        $this->doc = new \DOMDocument();
        $this->doc->loadXML($content);
    }

    /**
     * @param string $path
     */
    public function setTmpPath($path)
    {
        $this->tmpPath = $path;
    }

    /**
     * @param string $html
     * @param array $config
     * @return string
     */
    protected function purifyHTML($html, $config)
    {
        $configInstance               = \HTMLPurifier_Config::create($config);
        $configInstance->autoFinalize = false;
        $purifier                     = \HTMLPurifier::instance($configInstance);
        $purifier->config->set('Cache.SerializerPath', $this->tmpPath);

        return $purifier->purify($html);
    }

    /**
     * @param string $html
     * @return \DOMNode
     */
    public function html2DOM($html)
    {
        $html = $this->purifyHTML(
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

        return $bodies->item(0);
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
        echo htmlentities($this->doc->saveXML(), ENT_COMPAT, 'UTF-8');
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
    protected function appendParagraphStyleNode($styleName, $attributes)
    {
        $this->appendStyleNode($styleName, 'paragraph', 'paragraph-properties', $attributes);
    }
}
