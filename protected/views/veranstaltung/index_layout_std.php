<?php
/**
 * @var array $antraege
 * @var Veranstaltung $veranstaltung
 */

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