<?php

/**
 * @var VeranstaltungController $this
 * @var Veranstaltung $veranstaltung
 * @var Standardtext $einleitungstext
 * @var array $antraege
 * @var null|Person $ich
 * @var array|AntragKommentar[] $neueste_kommentare
 * @var array|Antrag[] $neueste_antraege
 * @var array|Aenderungsantrag[] $neueste_aenderungsantraege
 * @var array|AntragUnterstuetzerInnen[] $meine_antraege
 * @var array|AenderungsantragUnterstuetzerInnen[] $meine_aenderungsantraege
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

	<h1>
		<?php
		echo CHtml::encode($veranstaltung->name);
		if ($veranstaltung->datum_von != "" && $veranstaltung->datum_von != "0000-00-00") {
			if ($veranstaltung->datum_von != $veranstaltung->datum_bis) {
				echo ", " . HtmlBBcodeUtils::formatMysqlDate($veranstaltung->datum_von) . " - " . HtmlBBcodeUtils::formatMysqlDate($veranstaltung->datum_bis);
			} else {
				echo ", " . HtmlBBcodeUtils::formatMysqlDate($veranstaltung->datum_von);
			}

		}
		$editlink = $einleitungstext->getEditLink();
		if ($editlink !== null) echo "<a style='font-size: 10px;' href='" . CHtml::encode($this->createUrl($editlink[0], $editlink[1])) . "'>Bearbeiten</a>";
		?>
	</h1>

	<div class='content' style='overflow: auto;'>
		<?php
		if ($veranstaltung->antragsschluss != "") echo '<p class="antragsschluss_kreis">Antrags&shy;schluss: ' . HtmlBBcodeUtils::formatMysqlDateTime($veranstaltung->antragsschluss) . "</p>\n";

		echo $einleitungstext->getHTMLText();

		?>
	</div>
<?php

if (in_array($this->veranstaltung->id, array(134, 149, 145))) {
	require_once(__DIR__ . "/index_layout_tags.php");
} elseif ($this->veranstaltung->getEinstellungen()->bdk_startseiten_layout) {
	require_once(__DIR__ . "/index_layout_bdk.php");
} else {
	require_once(__DIR__ . "/index_layout_std.php");
}
?>

<?php if ($ich) { ?>
	<?php
	if (count($meine_antraege) > 0) {
		?>
		<h3><?= $sprache->get("Meine Anträge") ?></h3>
		<div class="content">
			<ul class="antragsliste">
				<?php foreach ($meine_antraege as $antragu) {
					$antrag = $antragu->antrag;
					echo "<li>";
					if ($antrag->status == Antrag::$STATUS_ZURUECKGEZOGEN) echo "<span style='text-decoration: line-through;'>";
					echo CHtml::link(CHtml::encode($antrag->name), $this->createUrl('antrag/anzeige', array("antrag_id" => $antrag->id)));
					if ($antragu->rolle == AntragUnterstuetzerInnen::$ROLLE_INITIATORIN) echo " (InitiatorIn)";
					if ($antragu->rolle == AntragUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN) echo " (UnterstützerIn)";
					if ($antrag->status == Antrag::$STATUS_ZURUECKGEZOGEN) echo "</span>";
					echo "</li>\n";
				} ?>
			</ul>
		</div>
	<?php
	}

	if (count($meine_aenderungsantraege) > 0) {
		?>
		<h3><?= $sprache->get("Meine Änderungsanträge") ?></h3>
		<div class="content">
			<ul class="antragsliste">
				<?php foreach ($meine_aenderungsantraege as $antragu) {
					/** @var AenderungsantragUnterstuetzerInnen $antragu */
					/** @var Aenderungsantrag $antrag */
					$antrag = $antragu->aenderungsantrag;
					echo "<li>";
					if ($antrag->status == Aenderungsantrag::$STATUS_ZURUECKGEZOGEN) echo "<span style='text-decoration: line-through;'>";
					echo CHtml::link(CHtml::encode($antrag->revision_name . " zu " . $antrag->antrag->revision_name),
						$this->createUrl('aenderungsantrag/anzeige', array("antrag_id" => $antrag->antrag->id, "aenderungsantrag_id" => $antrag->id)));
					if ($antragu->rolle == AenderungsantragUnterstuetzerInnen::$ROLLE_INITIATORIN) echo " (InitiatorIn)";
					if ($antragu->rolle == AenderungsantragUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN) echo " (UnterstützerIn)";
					if ($antrag->status == Aenderungsantrag::$STATUS_ZURUECKGEZOGEN) echo "</span>";
					echo "</li>\n";
				} ?>
			</ul>
		</div>
	<?php
	}
} // ich
