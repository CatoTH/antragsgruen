<?php

/**
 * @var VeranstaltungController $this
 * @var Veranstaltung $veranstaltung
 * @var array $antraege
 * @var null|Person $ich
 * @var array|AntragKommentar[] $neueste_kommentare
 * @var array|Antrag[] $neueste_antraege
 * @var array|Aenderungsantrag[] $neueste_aenderungsantraege
 * @var Sprache $sprache
 * @var string $suchbegriff
 * @var array|Antrag[] $suche_antraege
 * @var array|Aenderungsantrag[] $suche_aenderungsantraege
 */


$this->pageTitle = Yii::app()->name;

$this->breadcrumbs         = array(
	CHtml::encode($veranstaltung->name_kurz),
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");

$this->pageTitle = $veranstaltung->name . " (Antragsgrün)";

include(__DIR__ . "/sidebar.php");


?>
<h1 class="well">Suche nach "<?php echo CHtml::encode($suchbegriff); ?>"</h1>
<div class="well well_first">
	<div class="content">
		<strong>Hinweis:</strong> gefunden werden nur (Änderungs-)Anträge, in denen <i>genau</i> die angegebene Zeichenkette vorkommt.<br>
	</div>
</div>

<h3>Gefundene Anträge</h3>
<?php
if (count($suche_antraege) > 0) {
	echo "<ul>";
	foreach ($suche_antraege as $antrag) {
		/** @var Antrag $antrag */
		echo "<li";
		switch ($antrag->typ) {
			case Antrag::$TYP_ANTRAG:
				echo " class='antrag'";
				break;
			case Antrag::$TYP_RESOLUTION:
				echo " class='antrag resolution'";
				break;
			default:
				echo " class='antrag resolution'";
		}
		echo ">";
		echo "<p class='datum'>" . HtmlBBcodeUtils::formatMysqlDate($antrag->datum_einreichung) . "</p>\n";
		echo "<p class='titel'>\n";
		echo CHtml::link(CHtml::encode($antrag->nameMitRev()), $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
		echo CHtml::link("PDF", $this->createUrl("antrag/pdf", array("antrag_id" => $antrag->id)), array("class" => "pdfLink"));
		echo "</p>\n";
		echo "<p class='info'>";
		$text       = $antrag->text . "\n" . $antrag->begruendung;
		$last_found = 0;
		for ($i = 0; $i < 3 && $last_found !== false; $i++) {
			$last_found = mb_stripos($text, $suchbegriff, $last_found);
			if ($last_found !== false) {
				$from   = ($last_found > 65 ? $last_found - 65 : 0);
				$substr = CHtml::encode(HtmlBBcodeUtils::removeBBCode(mb_substr($text, $from, ($last_found > 65 ? 65 : $last_found))));
				$substr .= "<strong>" . CHtml::encode(mb_substr($text, $last_found, mb_strlen($suchbegriff))) . "</strong>";
				$substr .= CHtml::encode(HtmlBBcodeUtils::removeBBCode(mb_substr($text, $last_found + mb_strlen($suchbegriff), 65)));
				echo "<i>..." . $substr . "...</i><br style='margin-bottom: 3px;'>";
			}
			$last_found++;
		}

		echo "</p>";
		echo "</li>";
	}
	echo "</ul>";
} else echo "<div class='well'><div class='content'><i>keine Anträge gefunden</i></div></div> ";
?>

<h3>Gefundene Änderungsanträge</h3>
<?php
if (count($suche_aenderungsantraege) > 0) {
	echo "<ul>";
	foreach ($suche_aenderungsantraege as $aenderungsantrag) {
		echo "<li";
		switch ($aenderungsantrag->antrag->typ) {
			case Antrag::$TYP_ANTRAG:
				echo " class='antrag'";
				break;
			case Antrag::$TYP_RESOLUTION:
				echo " class='antrag resolution'";
				break;
			default:
				echo " class='antrag resolution'";
		}
		echo ">";
		echo "<p class='datum'>" . HtmlBBcodeUtils::formatMysqlDate($aenderungsantrag->antrag->datum_einreichung) . "</p>\n";
		echo "<p class='titel'>\n";
		echo CHtml::link(CHtml::encode($aenderungsantrag->revision_name . " zu " . $aenderungsantrag->antrag->nameMitRev()), $this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id)));
		echo CHtml::link("PDF", $this->createUrl("aenderungsantrag/pdf", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id)), array("class" => "pdfLink"));
		echo "</p>\n";
		echo "<p class='info'>";
		$text       = $aenderungsantrag->aenderung_text . "\n" . $aenderungsantrag->aenderung_begruendung;
		$last_found = 0;
		for ($i = 0; $i < 3 && $last_found !== false; $i++) {
			$last_found = mb_stripos($text, $suchbegriff, $last_found);
			if ($last_found !== false) {
				$from   = ($last_found > 65 ? $last_found - 65 : 0);
				$substr = CHtml::encode(HtmlBBcodeUtils::removeBBCode(mb_substr($text, $from, ($last_found > 65 ? 65 : $last_found))));
				$substr .= "<strong>" . CHtml::encode(mb_substr($text, $last_found, mb_strlen($suchbegriff))) . "</strong>";
				$substr .= CHtml::encode(HtmlBBcodeUtils::removeBBCode(mb_substr($text, $last_found + mb_strlen($suchbegriff), 65)));
				echo "<i>..." . $substr . "...</i><br style='margin-bottom: 3px;'>";
			}
			$last_found++;
		}

		echo "</p>";
		echo "</li>";
	}
	echo "</ul>";
} else echo "<div class='well'><div class='content'><i>keine Anträge gefunden</i></div></div> ";
?>

