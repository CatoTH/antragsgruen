<?php

/**
 * @var SiteController $this
 * @var Veranstaltung $veranstaltung
 * @var Standardtext $einleitungstext
 * @var array $antraege
 * @var null|Person $ich
 * @var array|AntragKommentar[] $neueste_kommentare
 * @var array|Antrag[] $neueste_antraege
 * @var array|Aenderungsantrag[] $neueste_aenderungsantraege
 * @var array|AntragUnterstuetzer[] $meine_antraege
 * @var array|AenderungsantragUnterstuetzer[] $meine_aenderungsantraege
 * @var Sprache $sprache
 */

$this->pageTitle = Yii::app()->name;

$this->breadcrumbs = array(
	CHtml::encode($veranstaltung->name_kurz),
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");

$this->pageTitle = $veranstaltung->name . " (Antragsgrün)";

include(__DIR__ . "/sidebar.php");

?>

<h1 class="well">
	<?php
	echo CHtml::encode($veranstaltung->name);
	if ($veranstaltung->datum_von != "" && $veranstaltung->datum_von != "0000-00-00") {
		if ($veranstaltung->datum_von != $veranstaltung->datum_bis) {
			echo ", " . HtmlBBcodeUtils::formatMysqlDate($veranstaltung->datum_von) . " - " . HtmlBBcodeUtils::formatMysqlDate($veranstaltung->datum_bis);
		} else {
			echo ", " . HtmlBBcodeUtils::formatMysqlDate($veranstaltung->datum_von);
		}

	}
	if ($einleitungstext->getEditLink() !== null) echo "<a style='font-size: 10px;' href='" . CHtml::encode($einleitungstext->getEditLink()) . "'>Bearbeiten</a>";
	?>
</h1>

<div class="well well_first">
    <div class='content' style='overflow: auto;'>
		<?php
		if ($veranstaltung->antragsschluss != "") echo '<p class="antragsschluss">Antrags&shy;schluss: ' . HtmlBBcodeUtils::formatMysqlDateTime($veranstaltung->antragsschluss) . "</p>\n";

		echo $einleitungstext->getHTMLText();
		?>
    </div>
</div>
<?php
foreach ($antraege as $name=> $antrs) {
	echo "<h3>" . CHtml::encode($name) . "</h3>";
	echo "<ul>";
	foreach ($antrs as $antrag) {
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
		echo CHtml::link(CHtml::encode($antrag->nameMitRev()), $this->createUrl('antrag/anzeige', array("antrag_id" => $antrag->id)));
		echo CHtml::link("PDF", $this->createUrl('antrag/pdf', array("antrag_id" => $antrag->id)), array("class"=> "pdfLink"));
		echo "</p>\n";
		echo "<p class='info'>von ";
		$vons = array();
		foreach ($antrag->antragUnterstuetzer as $unt) if ($unt->rolle == "initiator") $vons[] = $unt->unterstuetzer->name;
		echo implode(", ", $vons);
		echo ", " . CHtml::encode(IAntrag::$STATI[$antrag->status]);
		echo "</p>";

		if (count($antrag->aenderungsantraege) > 0) {
			echo "<ul class='aenderungsantraege'>";
			foreach ($antrag->aenderungsantraege as $ae) {
				echo "<li>";
				echo "<span class='datum'>" . HtmlBBcodeUtils::formatMysqlDate($ae->datum_einreichung) . "</span>\n";
				echo CHtml::link($ae->revision_name, $this->createUrl('aenderungsantrag/anzeige', array("antrag_id" => $ae->antrag->id, "aenderungsantrag_id" => $ae->id)));
				$vons = array();
				foreach ($ae->aenderungsantragUnterstuetzer as $unt) if ($unt->rolle == "initiator") $vons[] = $unt->unterstuetzer->name;
				echo "<span class='info'>" . implode(", ", $vons) . "</span>\n";
				echo "</li>\n";
			}
			echo "</ul>";
		}
		echo "</li>\n";
	}
	echo "</ul>";
}
?>

<?php if ($ich) { ?>
<div class="well">
	<?php
	if (count($meine_antraege) > 0) {
		?>
        <h3><?=$sprache->get("Meine Anträge")?></h3>
        <div class="content">
            <ul>
				<?php foreach ($meine_antraege as $antragu) {
				$antrag = $antragu->antrag;
				echo "<li>";
				echo CHtml::link(CHtml::encode($antrag->name), $this->createUrl('antrag/anzeige', array("antrag_id" => $antrag->id)));
				if ($antragu->rolle == AntragUnterstuetzer::$ROLLE_INITIATOR) echo " (InitiatorIn)";
				if ($antragu->rolle == AntragUnterstuetzer::$ROLLE_UNTERSTUETZER) echo " (UnterstützerIn)";
				echo "</li>\n";
			} ?>
            </ul>
        </div>
		<?php
	}

	if (count($meine_aenderungsantraege) > 0) {
		?>
        <h3><?=$sprache->get("Meine Änderungsanträge")?></h3>
        <div class="content">
            <ul>
				<?php foreach ($meine_aenderungsantraege as $antragu) {
				/** @var AenderungsantragUnterstuetzer $antragu */
				/** @var Aenderungsantrag $antrag */
				$antrag = $antragu->aenderungsantrag;
				echo "<li>";
				echo CHtml::link(CHtml::encode($antrag->revision_name . " zu " . $antrag->antrag->revision_name),
					$this->createUrl('aenderungsantrag/anzeige', array("antrag" => $antrag->antrag->id, "aenderungsantrag_id" => $antrag->id)));
				if ($antragu->rolle == AenderungsantragUnterstuetzer::$ROLLE_INITIATOR) echo " (InitiatorIn)";
				if ($antragu->rolle == AenderungsantragUnterstuetzer::$ROLLE_UNTERSTUETZER) echo " (UnterstützerIn)";
				echo "</li>\n";
			} ?>
            </ul>
        </div>
		<?php
	}
	?></div><?php
} // ich
