<?php
/**
 * @var array $antraege
 * @var Veranstaltung $veranstaltung
 */

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