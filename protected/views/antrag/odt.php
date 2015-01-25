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

$DEBUG = false;

if ($DEBUG) echo "<pre>";

$doc = new OdtTemplateEngine($content);

/** @var array|string[] $initiatorinnen */
$initiatorinnen = array();
$unterstuetzerInnen = array();
foreach ($model->antragUnterstuetzerInnen as $unt) {
	if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) $initiatorinnen[] = $unt->person->name;
	if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN) $unterstuetzerInnen[] = $unt->person;
}
$doc->addReplace("/\{\{ANTRAGSGRUEN:TITEL\}\}/siu", $model->name);
$doc->addReplace("/\{\{ANTRAGSGRUEN:ANTRAGSTELLERINNEN\}\}/siu", implode(", ", $initiatorinnen));


if ($DEBUG) $doc->debugOutput();

$absae = $model->getParagraphs();
$content = $doc->convert($absae, $model->begruendung);

$zip->deleteName("content.xml");
$zip->addFromString("content.xml", $content);
$zip->close();


Header("Content-Type: application/vnd.oasis.opendocument.text");
header('Content-disposition: filename="' . addslashes('Antrag_' . $model->revision_name . '.odt') . '"');

readfile($tmpZipFile);
unlink($tmpZipFile);
