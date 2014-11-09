<?php
/**
 * @var VeranstaltungController $this
 * @var array $antraege
 * @var Veranstaltung $veranstaltung
 */

$tags   = $tag_ids = array();
$hat_na = false;

foreach ($antraege as $antraege2) {
	foreach ($antraege2 as $antrag) {
		/** @var Antrag $antrag */
		if (count($antrag->tags) == 0) {
			$hat_na = true;
			if (!isset($tags[0])) $tags[0] = array("name" => "Keines", "antraege" => array());
			$tags[0]["antraege"][] = $antrag;
		} else foreach ($antrag->tags as $tag) {
			if (!isset($tags[$tag->id])) $tags[$tag->id] = array("name" => $tag->name, "antraege" => array());
			$tags[$tag->id]["antraege"][] = $antrag;
		}
	}
}
foreach ($this->veranstaltung->tags as $tag) if (isset($tags[$tag->id])) $tag_ids[] = $tag->id;
if ($hat_na) $tag_ids[] = 0;


?>
	<h3>Themenbereiche</h3>
	<br><br>
	<style>
		#tag_list {
			display: block;
			list-style-type: none;
			margin: 0;
			padding: 0;
			text-align: center;
		}
		#tag_list > li {
			display: inline-block;
			padding: 10px;
			background-color: #e2007a;
			border-radius: 3px;
			font-size: 16px;
			margin: 10px;
		}
		#tag_list > li > a:link, #tag_list > li > a:visited {
			color: white;
		}
	</style>
	<ul id="tag_list"><?php
		foreach ($tag_ids as $tag_id) {
			echo '<li><a href="#tag_' . $tag_id . '">' . CHtml::encode($tags[$tag_id]["name"]) . ' (' . count($tags[$tag_id]["antraege"]) . ')</a></li>';
		}
		?>
	</ul>
	<script>
		$("#tag_list").find("a").click(function(ev) {
			ev.preventDefault();
			$($(this).attr("href")).scrollintoview({top_offset: -100});
		})
	</script>

	<br><br>
	<br><br>

<?php

foreach ($tag_ids as $tag_id) {
	$tag = $tags[$tag_id];
	echo "<h3 id='tag_" . $tag_id . "'>" . CHtml::encode($tag["name"]) . "</h3>";
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
			foreach ($tag["antraege"] as $antrag) {
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
