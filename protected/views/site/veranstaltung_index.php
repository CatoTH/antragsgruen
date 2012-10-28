<?php $this->pageTitle = Yii::app()->name;

/**
 * @var Veranstaltung $veranstaltung
 * @var string|null $einleitungstext
 * @var array $antraege
 * @var null|Person $ich
 * @var array|AntragKommentar[] $neueste_kommentare
 * @var array|Antrag[] $neueste_antraege
 * @var array|Aenderungsantrag[] $neueste_aenderungsantraege
 * @var array|AntragUnterstuetzer[] $meine_antraege
 * @var array|AenderungsantragUnterstuetzer[] $meine_aenderungsantraege
 * @var Sprache $sprache
 */

$this->breadcrumbs = array(
	CHtml::encode($veranstaltung->name_kurz),
);

if ($veranstaltung->typ != Veranstaltung::$TYP_PROGRAMM) {
	$html = "<div class='well'><ul class='nav nav-list neue-antraege'><li class='nav-header'>Neue Anträge</li>";
	if (count($neueste_antraege) == 0) $html .= "<li><i>keine</i></li>";
	else foreach ($neueste_antraege as $ant) {
		$html .= "<li";
		switch ($ant->typ) {
			case Antrag::$TYP_ANTRAG:
				$html .= " class='antrag'";
				break;
			case Antrag::$TYP_RESOLUTION:
				$html .= " class='resolution'";
				break;
			default:
				$html .= " class='resolution'";
		}
		$html .= ">" . CHtml::link($ant["name"], "/antrag/anzeige/?id=" . $ant["id"]) . "</li>\n";
	}
	$html .= "</ul></div>";
	$this->menus_html[] = $html;
}

if ($veranstaltung->darfEroeffnenAntrag()) {
	$this->menus_html[] = '<a class="neuer-antrag" href="/antrag/neu/?veranstaltung=' . $veranstaltung->id . '">
<img alt="Neuen Antrag stellen" src="/css/img/neuer-antrag.png">
</a>';
}


$html = "<div class='well'><ul class='nav nav-list neue-aenderungsantraege'><li class='nav-header'>Neue Änderungsanträge</li>";
if (count($neueste_aenderungsantraege) == 0) $html .= "<li><i>keine</i></li>";
else foreach ($neueste_aenderungsantraege as $ant) {
	$html .= "<li class='aeantrag'>" . CHtml::link("<strong>" . CHtml::encode($ant["revision_name"]) . "</strong> zu " . CHtml::encode($ant->antrag->revision_name), "/aenderungsantrag/anzeige/?id=" . $ant["id"]) . "</li>\n";
}
$html .= "</ul></div>";
$this->menus_html[] = $html;

$html = "<div class='well'><ul class='nav nav-list neue-kommentare'><li class='nav-header'>Neue Kommentare</li>";
if (count($neueste_kommentare) == 0) $html .= "<li><i>keine</i></li>";
else foreach ($neueste_kommentare as $komm) {
	$html .= "<li class='komm'>";
	$html .= "<strong>" . CHtml::encode($komm->verfasser->name) . "</strong>, " . HtmlBBcodeUtils::formatMysqlDateTime($komm->datum);
	$html .= "<div>Zu " . CHtml::link(CHtml::encode($komm->antrag->name), "/antrag/anzeige/?id=" . $komm->antrag_id . "&kommentar=" . $komm->id . "#komm" . $komm->id) . "</div>";
	$html .= "</li>\n";
}
$html .= "</ul></div>";
$this->menus_html[] = $html;

$html = "<div class='well'><ul class='nav nav-list neue-kommentare'><li class='nav-header'>Feeds</li>";
if ($veranstaltung->typ != Veranstaltung::$TYP_PROGRAMM) $html .= "<li><a href='/site/feedAntraege/?id=" . $veranstaltung->id . "'>Anträge</a></li>";
$html .= "<li><a href='/site/feedAenderungsantraege/?id=" . $veranstaltung->id . "'>Änderungsanträge</a></li>";
$html .= "<li><a href='/site/feedKommentare/?id=" . $veranstaltung->id . "'>Kommentare</a></li>";
$html .= "<li><a href='/site/feedAlles/?id=" . $veranstaltung->id . "'><b>Alles</b></a></li>";
$html .= "</ul></div>";
$this->menus_html[] = $html;

?>

<h1 class="well">
	<?php
	echo CHtml::encode($veranstaltung->name);
	if ($veranstaltung->datum_von != "") {
		echo ", " . HtmlBBcodeUtils::formatMysqlDate($veranstaltung->datum_von) . " - " . HtmlBBcodeUtils::formatMysqlDate($veranstaltung->datum_von);
	}
	?>
</h1>

<div class="well well_first">
    <div class='content' style='overflow: auto;'>
		<?php
		if ($veranstaltung->antragsschluss != "") echo '<p class="antragsschluss">Antrags&shy;schluss: ' . HtmlBBcodeUtils::formatMysqlDateTime($veranstaltung->antragsschluss) . "</p>\n";

		if ($einleitungstext !== null) echo $einleitungstext;
		?>
        <br><br><span style="color: red; font-weight: bold;">BETA</span> - Bugs und Verbesserungsvorschläge auf <a href="https://textbegruenung.de/p/Antragstool_ToDo">https://textbegruenung.de/p/Antragstool_ToDo</a>
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
		echo CHtml::link(CHtml::encode($antrag->revision_name . ": " . $antrag->name), "/antrag/anzeige/?id=" . $antrag->id);
		echo CHtml::link("PDF", "/antrag/pdf/?id=" . $antrag->id, array("class"=> "pdfLink"));
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
				echo CHtml::link($ae->revision_name, "/aenderungsantrag/anzeige/?id=" . $ae->id);
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
        <h3>Meine Anträge</h3>
        <div class="content">
            <ul>
				<?php foreach ($meine_antraege as $antragu) {
				$antrag = $antragu->antrag;
				echo "<li>";
				echo CHtml::link(CHtml::encode($antrag->name), "/antrag/anzeige/?id=" . $antrag->id);
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
        <h3>Meine Änderungsanträge</h3>
        <div class="content">
            <ul>
				<?php foreach ($meine_aenderungsantraege as $antragu) {
				/** @var AenderungsantragUnterstuetzer $antragu */
				/** @var Aenderungsantrag $antrag */
				$antrag = $antragu->aenderungsantrag;
				echo "<li>";
				echo CHtml::link(CHtml::encode($antrag->revision_name . " zu " . $antrag->antrag->revision_name), "/aenderungsantrag/anzeige/?id=" . $antrag->id);
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