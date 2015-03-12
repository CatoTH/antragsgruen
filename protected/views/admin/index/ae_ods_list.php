<?php
/**
 * @var IndexController $this
 * @var array $antraege
 * @var bool $text_begruendung_zusammen
 * @var bool $antraege_separat
 * @var bool $zeilennummer_separat
 */

$curr_col = ord("B");

$first_col = chr($curr_col);
if ($antraege_separat) $COL_ANTRAGS_NR = chr($curr_col++);
$COL_AE_NR           = chr($curr_col++);
$COL_ANTRAGSTELLERIN = chr($curr_col++);
$COL_ZEILE           = chr($curr_col++);
$COL_ANTRAGSTEXT     = chr($curr_col++);
if (!$text_begruendung_zusammen) $COL_BEGRUENDUNG = chr($curr_col++);
$COL_KONTAKT   = chr($curr_col++);
$COL_VERFAHREN = chr($curr_col++);

$tmpZipFile = "/tmp/" . uniqid("zip-");
copy(Yii::app()->params['ods_default_template'], $tmpZipFile);

$zip = new ZipArchive();
if ($zip->open($tmpZipFile) !== TRUE) {
    die("cannot open <$tmpZipFile>\n");
}

$content = $zip->getFromName('content.xml');
$doc = new OdsTemplateEngine($content);

$DEBUG = false;

if ($DEBUG) {
    echo "<pre>";
    $doc->setDebug(true);
} else {
    Header("Content-Type: application/vnd.oasis.opendocument.spreadsheet");
    header('Content-disposition: filename="' . addslashes('Änderungsanträge.ods') . '"');
    header('Cache-Control: max-age=0');
}


$curr_col = $first_col = 1;
if ($antraege_separat) $COL_ANTRAGS_NR = $curr_col++;
$COL_AE_NR           = $curr_col++;
$COL_ANTRAGSTELLERIN = $curr_col++;
$COL_ZEILE           = $curr_col++;
$COL_ANTRAGSTEXT     = $curr_col++;
if (!$text_begruendung_zusammen) $COL_BEGRUENDUNG = $curr_col++;
$COL_KONTAKT   = $curr_col++;
$COL_VERFAHREN = $curr_col++;

$doc->setCell(1, $first_col, OdsTemplateEngine::$TYPE_TEXT, 'Antragsübersicht');
$doc->setCellStyle(1, $first_col, array(), array(
    "fo:font-size" => "16pt",
    "fo:font-weight" => "bold",
));
$doc->setMinRowHeight(1, 1.5);


if (isset($COL_ANTRAGS_NR)) {
    $doc->setCell(2, $COL_ANTRAGS_NR, OdsTemplateEngine::$TYPE_TEXT, 'Antragsnr.');
    $doc->setCellStyle(2, $COL_ANTRAGS_NR, array(), array("fo:font-weight" => "bold"));

}
$doc->setCell(2, $COL_AE_NR, OdsTemplateEngine::$TYPE_TEXT, 'ÄA-Nr.');

$doc->setCell(2, $COL_ANTRAGSTELLERIN, OdsTemplateEngine::$TYPE_TEXT, 'AntragstellerIn');
$doc->setColumnWidth($COL_ANTRAGSTELLERIN, 6);

$doc->setCell(2, $COL_ZEILE, OdsTemplateEngine::$TYPE_TEXT, 'Zeile');

$doc->setCell(2, $COL_ANTRAGSTEXT, OdsTemplateEngine::$TYPE_TEXT, 'Titel/Änderung');
$doc->setColumnWidth($COL_ANTRAGSTEXT, 10);

if (isset($COL_BEGRUENDUNG)) {
    $doc->setCell(2, $COL_BEGRUENDUNG, OdsTemplateEngine::$TYPE_TEXT, 'Begründung');
    $doc->setColumnWidth($COL_BEGRUENDUNG, 10);
}

$doc->setCell(2, $COL_KONTAKT, OdsTemplateEngine::$TYPE_TEXT, 'Kontakt');
$doc->setColumnWidth($COL_KONTAKT, 6);

$doc->setCell(2, $COL_VERFAHREN, OdsTemplateEngine::$TYPE_TEXT, 'Verfahren');
$doc->setColumnWidth($COL_VERFAHREN, 6);

$doc->drawBorder(1, $first_col, 2, $COL_VERFAHREN, 1.5);

$row = 3;
foreach ($antraege as $ant_nr => $ant) {
    /**
     * @var Antrag $antrag
     * @var Aenderungsantrag[] $aes
     */

    $antrag = $ant["antrag"];
    $aes    = $ant["aes"];

    if (!$antraege_separat) {
        $row++;
        $row++;

        $antrag_row_from = $row;

        $initiatorInnen_namen   = array();
        $initiatorInnen_kontakt = array();
        foreach ($antrag->antragUnterstuetzerInnen as $unt) {
            if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) {
                $initiatorInnen_namen[] = $unt->getNameMitBeschlussdatum(false);
                if ($unt->person->email != "") $initiatorInnen_kontakt[] = $unt->person->email;
                if ($unt->person->telefon != "") $initiatorInnen_kontakt[] = $unt->person->telefon;
            }
        }

        $doc->setCell($row, $COL_AE_NR, OdsTemplateEngine::$TYPE_TEXT, $antrag->revision_name);
        $doc->setCellStyle($row, $COL_AE_NR, array(), array("fo:font-weight" => "bold"));

        $doc->setCell($row, $COL_ANTRAGSTELLERIN, OdsTemplateEngine::$TYPE_TEXT, implode(", ", $initiatorInnen_namen));
        $doc->setCell($row, $COL_KONTAKT, OdsTemplateEngine::$TYPE_TEXT, implode("\n", $initiatorInnen_kontakt));
    }

    foreach ($aes as $ae_nr => $ae) {
        $row++;

        $initiatorInnen_namen   = array();
        $initiatorInnen_kontakt = array();
        foreach ($ae->aenderungsantragUnterstuetzerInnen as $unt) {
            if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) {
                $initiatorInnen_namen[] = $unt->person->getNameMitOrga();
                if ($unt->person->email != "") $initiatorInnen_kontakt[] = $unt->person->email;
                if ($unt->person->telefon != "") $initiatorInnen_kontakt[] = $unt->person->telefon;
            }
        }

        if (isset($COL_ANTRAGS_NR)) {
            $doc->setCell($row, $COL_ANTRAGS_NR, OdsTemplateEngine::$TYPE_TEXT, $ae->antrag->revision_name);
        }
        $doc->setCell($row, $COL_AE_NR, OdsTemplateEngine::$TYPE_TEXT, $ae->revision_name);
        $doc->setCell($row, $COL_ANTRAGSTELLERIN, OdsTemplateEngine::$TYPE_TEXT, implode(", ", $initiatorInnen_namen));
        $doc->setCell($row, $COL_KONTAKT, OdsTemplateEngine::$TYPE_TEXT, implode("\n", $initiatorInnen_kontakt));
        $doc->setCell($row, $COL_ZEILE, OdsTemplateEngine::$TYPE_TEXT, $ae->getFirstDiffLine());

        $absae = $ae->getAntragstextParagraphs_diff();
        $diffhtml = "";
        foreach ($absae as $i => $abs) if ($abs !== null) {
            $diffhtml .= $abs->getDiffHTMLForODS();
        }

        if ($ae->aenderung_begruendung_html) $text_begruendung = $ae->aenderung_begruendung;
        else $text_begruendung = '<div>' . HtmlBBcodeUtils::bbcode2html($ae->aenderung_begruendung) . '</div>';

        if (!isset($COL_BEGRUENDUNG)) {
            $text = '<p><strong>Änderungsantrag:</strong></p>' . $diffhtml;
            $text .= '<p><strong>Begründung:</strong></p>' . $text_begruendung;
            $doc->setCell($row, $COL_ANTRAGSTEXT, OdsTemplateEngine::$TYPE_HTML, $text);
        } else {
            if ($DEBUG) echo CHtml::encode($text_begruendung);
            $doc->setCell($row, $COL_ANTRAGSTEXT, OdsTemplateEngine::$TYPE_HTML, $diffhtml);
            $doc->setCell($row, $COL_BEGRUENDUNG, OdsTemplateEngine::$TYPE_HTML, $text_begruendung);
        }
    }

    if (!$antraege_separat) {
        /** @var int $antrag_row_from */
        $doc->drawBorder($antrag_row_from, $first_col, $row, $COL_VERFAHREN, 1.5);
    }
}


$content = $doc->create();

if ($DEBUG) $doc->debugOutput();

$zip->deleteName("content.xml");
$zip->addFromString("content.xml", $content);
$zip->close();


readfile($tmpZipFile);
unlink($tmpZipFile);
