<?php


class OdtTemplateEngine {
	public static $TEMPLATE_TYPE_ANTRAG = 0;
	public static $TEMPLATE_TYPE_BEGRUENDUNG = 1;

	/** @var DOMDocument */
	private $doc = null;

	/** @var null|DOMElement */
	private $node_template_1 = null;
	/** @var null|DOMElement */
	private $node_template_n = null;
	/** @var null|DOMElement */
	private $node_begruendung = null;

	/** @var bool */
	private $node_template_1_used = false;

	private $replaces = array();

	/** @var bool */
	private $DEBUG = false;

	public function __construct($content)
	{
		$this->doc = new DOMDocument();
		$this->doc->loadXML($content);
	}

	/***
	 * @param bool $debug
	 */
	public function setDebug($debug) {
		$this->DEBUG = $debug;
	}

	public function debugOutput() {
		$this->doc->preserveWhiteSpace = false;
		$this->doc->formatOutput       = true;
		echo CHtml::encode($this->doc->saveXML());
		die();
	}

	/**
	 * @param string $search
	 * @param string $replace
	 */
	public function addReplace($search, $replace) {
		$this->replaces[$search] = $replace;
	}

	/**
	 * @param string[]
	 * @param string
	 * @return string
	 */
	public function convert($antrag_absaetze, $begruendung) {
		$this->appendStyleNode("Antragsgruen_fett", array(
			"fo:font-weight"            => "bold",
			"style:font-weight-asian"   => "bold",
			"style:font-weight-complex" => "bold",
		));
		$this->appendStyleNode("Antragsgruen_kursiv", array(
			"fo:font-style"            => "italic",
			"style:font-style-asian"   => "italic",
			"style:font-style-complex" => "italic",
		));
		$this->appendStyleNode("Antragsgruen_unterstrichen", array(
			"style:text-underline-width" => "auto",
			"style:text-underline-color" => "font-color",
			"style:text-underline-style" => "solid",
		));
		$this->appendStyleNode("Antragsgruen_gruen", array(
			"fo:color" => "#00ff00",
		));
		$this->appendStyleNode("Antragsgruen_rot", array(
			"fo:color" => "#ff0000",
		));

		/** @var DOMNode[] $nodes */
		$nodes = array();
		foreach ($this->doc->getElementsByTagNameNS(ODT_NS_TEXT, 'span') as $element) $nodes[] = $element;
		foreach ($this->doc->getElementsByTagNameNS(ODT_NS_TEXT, 'p') as $element) $nodes[] = $element;


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
				$new_node = $this->html2odtNode($begruendung, static::$TEMPLATE_TYPE_BEGRUENDUNG);
				$this->node_begruendung->parentNode->insertBefore($new_node, $this->node_begruendung);
			}
			$this->node_begruendung->parentNode->removeChild($this->node_begruendung);
		}

		if ($this->node_template_1) {
			$html     = HtmlBBcodeUtils::bbcode2html($antrag_absaetze[0]->str_bbcode);
			if ($this->DEBUG) echo "======<br>" . nl2br(CHtml::encode($html)) . "<br>========<br>";
			$new_node = $this->html2odtNode($html, static::$TEMPLATE_TYPE_ANTRAG);
			$this->node_template_1->parentNode->insertBefore($new_node, $this->node_template_1);
			$this->node_template_1->parentNode->removeChild($this->node_template_1);
		}
		if ($this->node_template_n) {
			for ($i = 1; $i < count($antrag_absaetze); $i++) {
				$html     = HtmlBBcodeUtils::bbcode2html($antrag_absaetze[$i]->str_bbcode);
				if ($this->DEBUG) echo "======<br>" . nl2br(CHtml::encode($html)) . "<br>========<br>";
				$new_node = $this->html2odtNode($html, static::$TEMPLATE_TYPE_ANTRAG);
				$this->node_template_n->parentNode->insertBefore($new_node, $this->node_template_n);
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
	private function getNextNodeTemplate($template_type) {
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

	/**
	 * @param string $style_name
	 * @param array $attributes
	 */
	private function appendStyleNode($style_name, $attributes)
	{
		$node = $this->doc->createElementNS(ODT_NS_STYLE, "style");
		$node->setAttribute("style:name", $style_name);
		$node->setAttribute("style:family", "text");

		$style = $this->doc->createElementNS(ODT_NS_STYLE, "text-properties");
		foreach ($attributes as $att_name => $att_val) {
			$style->setAttribute($att_name, $att_val);
		}
		$node->appendChild($style);

		foreach ($this->doc->getElementsByTagNameNS(ODT_NS_OFFICE, 'automatic-styles') as $element) {
			/** @var DOMElement $element */
			$element->appendChild($node);
		}
	}

	/**
	 * @param DOMNode $src_node
	 * @param int $template_type
	 * @return \DOMText|null
	 */
	private function html2odtNode_int($src_node, $template_type)
	{
		switch ($src_node->nodeType) {
			case XML_ELEMENT_NODE:
				/** @var DOMElement $src_node */
				if ($this->DEBUG) echo "Element - " . $src_node->nodeName . " / Children: " . count($src_node->childNodes) . "<br>";
				$append_el = null;
				switch ($src_node->nodeName) {
					case "b":
						$dst_el = $this->doc->createElementNS(ODT_NS_TEXT, "span");
						$dst_el->setAttribute("text:style-name", "Antragsgruen_fett");
						break;
					case "i":
						$dst_el = $this->doc->createElementNS(ODT_NS_TEXT, "span");
						$dst_el->setAttribute("text:style-name", "Antragsgruen_kursiv");
						break;
					case "u":
						$dst_el = $this->doc->createElementNS(ODT_NS_TEXT, "span");
						$dst_el->setAttribute("text:style-name", "Antragsgruen_unterstrichen");
						break;
					case "br":
						$dst_el = $this->doc->createElementNS(ODT_NS_TEXT, "line-break");
						break;
					case "ul":
						$dst_el = $this->doc->createElementNS(ODT_NS_TEXT, "list");
						break;
					case "li":
						$dst_el    = $this->doc->createElementNS(ODT_NS_TEXT, "list-item");
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
	private function html2odtNode($html, $template_type)
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
}

