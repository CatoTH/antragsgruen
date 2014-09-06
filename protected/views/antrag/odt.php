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

$NS_OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
$NS_TEXT   = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
$DEBUG     = false;

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
foreach ($doc->getElementsByTagNameNS($NS_TEXT, 'span') as $element) $nodes[] = $element;
foreach ($doc->getElementsByTagNameNS($NS_TEXT, 'p') as $element) $nodes[] = $element;

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
 * @param string $text
 * @param DOMNode $node_template
 * @return DOMNode[]
 */
function bbcode2odtNode($text, $node_template)
{
	$new_node = $node_template->cloneNode();
	$textnode = new DOMText();
	$textnode->data = $text;
	$new_node->appendChild($textnode);
	return array($new_node);
}

if ($node_begruendung) {
	$new_nodes = bbcode2odtNode($model->begruendung, $node_begruendung);
	foreach ($new_nodes as $new) {
		$node_begruendung->parentNode->insertBefore($new, $node_begruendung);
	}
	$node_begruendung->parentNode->removeChild($node_begruendung);
}

$absae = $model->getParagraphs();
if ($node_antrag_1) {
	$new_nodes = bbcode2odtNode($absae[0]->str_bbcode, $node_antrag_1);
	foreach ($new_nodes as $new) {
		$node_antrag_1->parentNode->insertBefore($new, $node_antrag_1);
	}
	$node_antrag_1->parentNode->removeChild($node_antrag_1);
}
if ($node_antrag_n) {
	for ($i = 1; $i < count($absae); $i++) {
		$new_nodes = bbcode2odtNode($absae[$i]->str_bbcode, $node_antrag_n);
		foreach ($new_nodes as $new) {
			$node_antrag_n->parentNode->insertBefore($new, $node_antrag_n);
		}
	}
	$node_antrag_n->parentNode->removeChild($node_antrag_n);
}


$content = $doc->saveXML();

if ($DEBUG) {
	echo CHtml::encode($content);
	die();
}

$zip->deleteName("content.xml");
$zip->addFromString("content.xml", $content);
$zip->close();


Header("Content-Type: application/vnd.oasis.opendocument.text");
header('Content-disposition: filename="' . addslashes('Antrag_' . $model->revision_name . '.odt') . '"');

readfile($tmpZipFile);
unlink($tmpZipFile);
