<?php
/**
 * @var Antrag $model
 * @var Sprache $sprache
 * @var AntragController $this
 */

$template = $model->getOdtTemplate();

$tmpZipFile = "/tmp/" . uniqid("zip-");
file_put_contents($tmpZipFile, $template->data);

$zip = new ZipArchive();
if ($zip->open($tmpZipFile) !== TRUE) {
	die("cannot open <$tmpZipFile>\n");
}

$content = $zip->getFromName('content.xml');

define("ODT_NS_OFFICE", 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
define("ODT_NS_TEXT", 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
define("ODT_NS_FO", 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0');
define("ODT_NS_STYLE", 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');

$DEBUG = false;

$doc = new DOMDocument();
$doc->loadXML($content);

if ($DEBUG) echo "<pre>";

/** @var array|string[] $initiatorinnen */
$initiatorinnen = array();

$unterstuetzerInnen = array();
foreach ($model->antragUnterstuetzerInnen as $unt) {
	if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) $initiatorinnen[] = $unt->person->name;
	if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN) $unterstuetzerInnen[] = $unt->person;
}

$PREG_REPLACES = array(
	"/\{\{ANTRAGSGRUEN:TITEL\}\}/siu"              => $model->name,
	"/\{\{ANTRAGSGRUEN:ANTRAGSTELLERINNEN\}\}/siu" => implode(", ", $initiatorinnen),
);

if ($DEBUG) var_dump($PREG_REPLACES);

$node_begruendung = $node_antrag_1 = $node_antrag_n = null;
/** @var DOMNode[] $nodes */
$nodes = array();
foreach ($doc->getElementsByTagNameNS(ODT_NS_TEXT, 'span') as $element) $nodes[] = $element;
foreach ($doc->getElementsByTagNameNS(ODT_NS_TEXT, 'p') as $element) $nodes[] = $element;

foreach ($nodes as $node) {
	$children = $node->childNodes;
	foreach ($children as $child) if ($child->nodeType == XML_TEXT_NODE) {
		/** @var DOMText $child */
		$child->data = preg_replace(array_keys($PREG_REPLACES), array_values($PREG_REPLACES), $child->data);

		if (preg_match("/\{\{ANTRAGSGRUEN:BEGRUENDUNG( [^\}]*)?/siu", $child->data)) $node_begruendung = $node;
		if (preg_match("/\{\{ANTRAGSGRUEN:ANTRAGSTEXT_1( [^\}]*)?/siu", $child->data)) $node_antrag_1 = $node;
		if (preg_match("/\{\{ANTRAGSGRUEN:ANTRAGSTEXT_N( [^\}]*)?/siu", $child->data)) $node_antrag_n = $node;
	}
}

/**
 * @param DOMDocument $document
 * @param string $style_name
 * @param array $attributes
 */
function appendStyleNode(&$document, $style_name, $attributes)
{
	$node = $document->createElementNS(ODT_NS_STYLE, "style");
	$node->setAttribute("style:name", $style_name);
	$node->setAttribute("style:family", "text");

	$style = $document->createElementNS(ODT_NS_STYLE, "text-properties");
	foreach ($attributes as $att_name => $att_val) {
		$style->setAttribute($att_name, $att_val);
	}
	$node->appendChild($style);

	foreach ($document->getElementsByTagNameNS(ODT_NS_OFFICE, 'automatic-styles') as $element) {
		/** @var DOMElement $element */
		$element->appendChild($node);
	}
}

appendStyleNode($doc, "Antragsgruen_fett", array(
	"fo:font-weight"            => "bold",
	"style:font-weight-asian"   => "bold",
	"style:font-weight-complex" => "bold",
));
appendStyleNode($doc, "Antragsgruen_kursiv", array(
	"fo:font-style"            => "italic",
	"style:font-style-asian"   => "italic",
	"style:font-style-complex" => "italic",
));
appendStyleNode($doc, "Antragsgruen_unterstrichen", array(
	"style:text-underline-width" => "auto",
	"style:text-underline-color" => "font-color",
	"style:text-underline-style" => "solid",
));
appendStyleNode($doc, "Antragsgruen_gruen", array(
	"fo:color" => "#00ff00",
));
appendStyleNode($doc, "Antragsgruen_rot", array(
	"fo:color" => "#ff0000",
));

/**
 * @param DOMDocument $document
 * @param DOMNode $src_node
 * @param bool $DEBUG
 * @return \DOMText|null
 */
function html2odtNode_int($document, $src_node, $DEBUG)
{
	switch ($src_node->nodeType) {
		case XML_ELEMENT_NODE:
			/** @var DOMElement $src_node */
			if ($DEBUG) echo "Element - " . $src_node->nodeName . " / Children: " . count($src_node->childNodes) . "<br>";
			$append_el = null;
			switch ($src_node->nodeName) {
				case "b":
					$dst_el = $document->createElementNS(ODT_NS_TEXT, "span");
					$dst_el->setAttribute("text:style-name", "Antragsgruen_fett");
					break;
				case "i":
					$dst_el = $document->createElementNS(ODT_NS_TEXT, "span");
					$dst_el->setAttribute("text:style-name", "Antragsgruen_kursiv");
					break;
				case "u":
					$dst_el = $document->createElementNS(ODT_NS_TEXT, "span");
					$dst_el->setAttribute("text:style-name", "Antragsgruen_unterstrichen");
					break;
				case "br":
					$dst_el = $document->createElementNS(ODT_NS_TEXT, "line-break");
					break;
				case "ul":
					$dst_el = $document->createElementNS(ODT_NS_TEXT, "list");
					break;
				case "li":
					$dst_el    = $document->createElementNS(ODT_NS_TEXT, "list-item");
					$append_el = $document->createElementNS(ODT_NS_TEXT, "p");
					$dst_el->appendChild($append_el);
					break;
				default:
					die("Unbekanntes Tag: " . $src_node->nodeName);
			}
			foreach ($src_node->childNodes as $child) {
				/** @var DOMNode $child */
				if ($DEBUG) echo $child->nodeType . "<br>";
				$dst_node = html2odtNode_int($document, $child, $DEBUG);
				if ($DEBUG) var_dump($dst_node);
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
			if ($DEBUG) echo "Text<br>";
			return $textnode;
			break;
		case XML_DOCUMENT_TYPE_NODE:
			if ($DEBUG) echo "Type Node<br>";
			return null;
			break;
		default:
			if ($DEBUG) echo "Unknown Node: " . $src_node->nodeType . "<br>";
			return null;
	}
}

/**
 * @param DOMDocument $document
 * @param string $html
 * @param DOMNode $node_template
 * @param bool $DEBUG
 * @return DOMNode
 */
function html2odtNode($document, $html, $node_template, $DEBUG)
{

	$src_doc = new DOMDocument();
	$src_doc->loadHTML($html);
	$bodies = $src_doc->getElementsByTagName("body");
	$body   = $bodies->item(0);

	$p = null;
	if (count($body->childNodes) == 1) {
		foreach ($body->childNodes as $child) {
			if ($child->nodeName == "p") $body = $child;
		}
	}

	$new_node = $node_template->cloneNode();
	foreach ($body->childNodes as $child) {
		/** @var DOMNode $child */
		if ($child->nodeName == "ul") {
			// Alle anderen Nocdes dieses Aufrufs werden ignoriert
			if ($DEBUG) echo "LIST<br>";
			$dst_node = html2odtNode_int($document, $child, $DEBUG);
			return $dst_node;
		} else {
			if ($DEBUG) echo $child->nodeName . "!!!!!!!!!!!!<br>";
			$dst_node = html2odtNode_int($document, $child, $DEBUG);
			if ($dst_node) $new_node->appendChild($dst_node);
		}
	}
	return $new_node;
}

if ($node_begruendung) {
	$new_node = html2odtNode($doc, $model->begruendung, $node_begruendung, $DEBUG);
	$node_begruendung->parentNode->insertBefore($new_node, $node_begruendung);
	$node_begruendung->parentNode->removeChild($node_begruendung);
}

$absae = $model->getParagraphs();
if ($node_antrag_1) {
	$html     = HtmlBBcodeUtils::bbcode2html($absae[0]->str_bbcode);
	if ($DEBUG) echo "======<br>" . nl2br(CHtml::encode($html)) . "<br>========<br>";
	$new_node = html2odtNode($doc, $html, $node_antrag_1, $DEBUG);
	$node_antrag_1->parentNode->insertBefore($new_node, $node_antrag_1);
	$node_antrag_1->parentNode->removeChild($node_antrag_1);
}
if ($node_antrag_n) {
	for ($i = 1; $i < count($absae); $i++) {
		$html     = HtmlBBcodeUtils::bbcode2html($absae[$i]->str_bbcode);
		if ($DEBUG) echo "======<br>" . nl2br(CHtml::encode($html)) . "<br>========<br>";
		$new_node = html2odtNode($doc, $html, $node_antrag_n, $DEBUG);
		$node_antrag_n->parentNode->insertBefore($new_node, $node_antrag_n);
	}
	$node_antrag_n->parentNode->removeChild($node_antrag_n);
}


if ($DEBUG) {
	$doc->preserveWhiteSpace = false;
	$doc->formatOutput       = true;
	echo CHtml::encode($doc->saveXML());
	die();
}

$content = $doc->saveXML();

$zip->deleteName("content.xml");
$zip->addFromString("content.xml", $content);
$zip->close();


Header("Content-Type: application/vnd.oasis.opendocument.text");
header('Content-disposition: filename="' . addslashes('Antrag_' . $model->revision_name . '.odt') . '"');

readfile($tmpZipFile);
unlink($tmpZipFile);
