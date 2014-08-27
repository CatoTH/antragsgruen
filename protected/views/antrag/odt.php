<?php
/**
 * @var Antrag $model
 * @var Sprache $sprache
 * @var AntragController $this
 */


Header("Content-Type: application/vnd.oasis.opendocument.text");
header('Content-disposition: filename="' . addslashes('Antrag_' . $model->revision_name . '.odt') . '"');

$phpWord = new \PhpOffice\PhpWord\PhpWord();

// Every element you want to append to the word document is placed in a section.
// To create a basic section:
$section = $phpWord->addSection();


$phpWord->addFontStyle('antragsgruen_ueberschrift',
	array('name' => 'Verdana', 'size' => 18, 'color' => '1B2232', 'bold' => true));
$section->addText($model->nameMitRev(), 'antragsgruen_ueberschrift');

$table = $section->addTable();
$table->addRow();
$table->addCell()->addText($sprache->get("Veranstaltung"));
$table->addCell()->addText($model->veranstaltung->name);
$table->addRow();
$table->addCell()->addText("AntragstellerIn:");
$x = array();
$antragstellerInnen = $model->getAntragstellerInnen();
foreach ($antragstellerInnen as $a) {
	$x[] = $a->name;
}
$table->addCell()->addText(implode(", ", $x));

$absae = $model->getParagraphs(true, false);
foreach ($absae as $i => $abs) {
	\PhpOffice\PhpWord\Shared\Html::addHtml($section, $abs->str_html_plain);
}

$file      = '/tmp/antragsgruen-' . rand(0, 100000000) . '.odt';
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'ODText');
$objWriter->save($file);
readfile($file);
unlink($file);