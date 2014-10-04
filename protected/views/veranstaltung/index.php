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

if ($this->veranstaltung->getEinstellungen()->bdk_startseiten_layout) {
	foreach ($antraege as $name => $antrs) {
		echo "<h3>" . CHtml::encode($name) . "</h3>";
		?>
		<div class="bdk_antrags_liste">
			<table>
				<thead>
				<tr>
					<th class="nummer">Antragsnummer</th>
					<th class="titel">Titel</th>
					<th class="antragstellerIn">AntragstellerIn</th>
				</tr>
				</thead>
				<?php
				foreach ($antrs as $antrag) {
					/** @var Antrag $antrag */
					$classes = array("antrag");
					if ($antrag->typ != Antrag::$TYP_ANTRAG) $classes[] = "resolution";
					if ($antrag->status == IAntrag::$STATUS_ZURUECKGEZOGEN) $classes[] = "zurueckgezogen";
					echo "<tr class='" . implode(" ", $classes) . "'>\n";
					echo "<td class='nummer'>" . CHtml::encode($antrag->revision_name) . "</td>\n";
					echo "<td class='titel'>";
					echo "<div class='titellink'>";
					echo CHtml::link(CHtml::encode($antrag->name), $this->createUrl('antrag/anzeige', array("antrag_id" => $antrag->id)));
					echo "</div><div class='pdflink'>";
					if ($veranstaltung->getEinstellungen()->kann_pdf) echo CHtml::link("als PDF", $this->createUrl('antrag/pdf', array("antrag_id" => $antrag->id)), array("class" => "pdfLink"));
					echo "</div></td><td class='antragstellerIn'>";
					$vons = array();
					foreach ($antrag->getAntragstellerInnen() as $p) $vons[] = $p->getNameMitOrga();
					echo implode(", ", $vons);
					if ($antrag->status != IAntrag::$STATUS_EINGEREICHT_GEPRUEFT) echo ", " . CHtml::encode(IAntrag::$STATI[$antrag->status]);
					echo "</td>";
					echo "</tr>";

					$aes = $antrag->sortierteAenderungsantraege();
					foreach ($aes as $ae) {
						echo "<tr class='aenderungsantrag " . ($ae->status == IAntrag::$STATUS_ZURUECKGEZOGEN ? " class='zurueckgezogen'" : "") . "'>";
						echo "<td class='nummer'>" . CHtml::encode($ae->revision_name) . "</td>\n";
						echo "<td class='titel'>";
						echo "<div class='titellink'>";
						echo CHtml::link("Änderungsantrag zu " . $antrag->revision_name, $this->createUrl('aenderungsantrag/anzeige', array("antrag_id" => $ae->antrag->id, "aenderungsantrag_id" => $ae->id)));
						echo "</div>";
						echo "</td><td class='antragstellerIn'>";
						$vons = array();
						foreach ($ae->getAntragstellerInnen() as $p) $vons[] = $p->getNameMitOrga();
						echo implode(", ", $vons);
						if ($ae->status != IAntrag::$STATUS_EINGEREICHT_GEPRUEFT) echo ", " . CHtml::encode(IAntrag::$STATI[$antrag->status]);
						echo "</td>";
						echo "</tr>";
					}
				}
				?>
			</table>
		</div>

	<?php


	}


} else {

	foreach ($antraege as $name => $antrs) {
		echo "<ul class='antragsliste'>";
		foreach ($antrs as $antrag) {
			/** @var Antrag $antrag */
			$classes = array("antrag");
			if ($antrag->typ != Antrag::$TYP_ANTRAG) $classes[] = "resolution";
			if ($antrag->status == IAntrag::$STATUS_ZURUECKGEZOGEN) $classes[] = "zurueckgezogen";
			echo "<li class='" . implode(" ", $classes) . "'>";
			echo "<p class='datum'>" . HtmlBBcodeUtils::formatMysqlDate($antrag->datum_einreichung) . "</p>\n";
			echo "<p class='titel'>\n";
			echo CHtml::link(CHtml::encode($antrag->nameMitRev()), $this->createUrl('antrag/anzeige', array("antrag_id" => $antrag->id)));
			if ($veranstaltung->getEinstellungen()->kann_pdf) echo CHtml::link("PDF", $this->createUrl('antrag/pdf', array("antrag_id" => $antrag->id)), array("class" => "pdfLink"));
			echo "</p>\n";
			echo "<p class='info'>von ";
			$vons = array();
			foreach ($antrag->getAntragstellerInnen() as $p) $vons[] = $p->getNameMitOrga();
			echo implode(", ", $vons);
			echo ", " . CHtml::encode(IAntrag::$STATI[$antrag->status]);
			echo "</p>";

			if (count($antrag->aenderungsantraege) > 0) {
				echo "<ul class='aenderungsantraege'>";
				$aes = $antrag->sortierteAenderungsantraege();
				foreach ($aes as $ae) {
					echo "<li" . ($ae->status == IAntrag::$STATUS_ZURUECKGEZOGEN ? " class='zurueckgezogen'" : "") . ">";
					echo "<span class='datum'>" . HtmlBBcodeUtils::formatMysqlDate($ae->datum_einreichung) . "</span>\n";
					$name = (trim($ae->revision_name) == "" ? "-" : $ae->revision_name);
					echo CHtml::link($name, $this->createUrl('aenderungsantrag/anzeige', array("antrag_id" => $ae->antrag->id, "aenderungsantrag_id" => $ae->id)));
					$vons = array();
					foreach ($ae->getAntragstellerInnen() as $p) $vons[] = $p->getNameMitOrga();
					echo "<span class='info'>" . implode(", ", $vons) . "</span>\n";
					echo "</li>\n";
				}
				echo "</ul>";
			}
			echo "</li>\n";
		}
		echo "</ul>";
	}
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
