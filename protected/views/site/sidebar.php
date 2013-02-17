<?php

/**
 * @var VeranstaltungenController $this
 * @var Veranstaltung $veranstaltung
 * @var string|null $einleitungstext
 * @var array $antraege
 * @var null|Person $ich
 * @var array|AntragKommentar[] $neueste_kommentare
 * @var array|Antrag[] $neueste_antraege
 * @var array|Aenderungsantrag[] $neueste_aenderungsantraege
 * @var Sprache $sprache
 */



$html = "<form class='form-search well hidden-phone' action='" . $this->createUrl("site/suche") . "' method='GET'><input type='hidden' name='id' value='" . $veranstaltung->id . "'><div class='nav-list'><div class='nav-header'>" . $sprache->get("Suche") . "</div>";
$html .= "<div style='text-align: center;'>  <div class='input-append'><input class='search-query' type='search' name='suchbegriff' value='' autofocus placeholder='Suchbegriff...'><button type='submit' class='btn'><i style='height: 18px;' class='icon-search'></i></button></div></div>";
$html .= "</div></form>";
$this->menus_html[] = $html;

if ($veranstaltung->typ != Veranstaltung::$TYP_PROGRAMM) {
	$html = "<div class='well'><ul class='nav nav-list neue-antraege'><li class='nav-header'>" . $sprache->get("Neue Anträge") . "</li>";
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
		$html .= ">" . CHtml::link($ant["name"], $this->createUrl("antrag/anzeige", array("antrag_id" => $ant["id"]))) . "</li>\n";
	}
	$html .= "</ul></div>";
	$this->menus_html[] = $html;

	if ($veranstaltung->darfEroeffnenAntrag()) {
		$this->menus_html[] = '<a class="neuer-antrag" href="' . CHtml::encode($this->createUrl("antrag/neu")) . '">
<img alt="Neuen Antrag stellen" src="/css/img/neuer-antrag.png">
</a>';
	}
}


$html = "<div class='well'><ul class='nav nav-list neue-aenderungsantraege'><li class='nav-header'>" . $sprache->get("Neue Änderungsanträge") . "</li>";
if (count($neueste_aenderungsantraege) == 0) $html .= "<li><i>keine</i></li>";
else foreach ($neueste_aenderungsantraege as $ant) {
	$html .= "<li class='aeantrag'>" . CHtml::link("<strong>" . CHtml::encode($ant["revision_name"]) . "</strong> zu " . CHtml::encode($ant->antrag->revision_name), $this->createUrl("aenderungsantrag/anzeige", array("aenderungsantrag_id" => $antrag->id, "antrag_id" => $ant->antrag->id))) . "</li>\n";
}
$html .= "</ul></div>";
$this->menus_html[] = $html;

if ($veranstaltung->typ == Veranstaltung::$TYP_PROGRAMM) {
	if ($veranstaltung->darfEroeffnenAntrag()) {
		$this->menus_html[] = '<a class="neuer-antrag" href="' . CHtml::encode($this->createUrl("antrag/neu")) . '">
<img alt="Neuen Antrag stellen" src="/css/img/neuer-antrag.png">
</a>';
	}
}

$html = "<div class='well'><ul class='nav nav-list neue-kommentare'><li class='nav-header'>Neue Kommentare</li>";
if (count($neueste_kommentare) == 0) $html .= "<li><i>keine</i></li>";
else foreach ($neueste_kommentare as $komm) {
	$html .= "<li class='komm'>";
	$html .= "<strong>" . CHtml::encode($komm->verfasser->name) . "</strong>, " . HtmlBBcodeUtils::formatMysqlDateTime($komm->datum);
	$html .= "<div>Zu " . CHtml::link(CHtml::encode($komm->antrag->name), $this->createUrl("antrag/anzeige", array("antrag_id" => $komm->antrag_id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id))) . "</div>";
	$html .= "</li>\n";
}
$html .= "</ul></div>";
$this->menus_html[] = $html;

$html = "<div class='well'><ul class='nav nav-list neue-kommentare'><li class='nav-header'>Feeds</li>";

if ($veranstaltung->typ != Veranstaltung::$TYP_PROGRAMM) $html .= "<li class='feed'>" . CHtml::link($sprache->get("Anträge"), $this->createUrl("site/feedAntraege")) . "</li>";
$html .= "<li class='feed'>" . CHtml::link($sprache->get("Änderungsanträge"), $this->createUrl("site/feedAenderungsantraege")) . "</li>";
$html .= "<li class='feed'>" . CHtml::link($sprache->get("Kommentare"), $this->createUrl("site/feedKommentare")) . "</li>";
$html .= "<li class='feed'>" . CHtml::link($sprache->get("Alles"), $this->createUrl("site/feedAlles")) . "</li>";
$html .= "</ul></div>";

$this->menus_html[] = $html;
