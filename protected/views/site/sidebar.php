<?php

/**
 * @var Veranstaltung $veranstaltung
 * @var string|null $einleitungstext
 * @var array $antraege
 * @var null|Person $ich
 * @var array|AntragKommentar[] $neueste_kommentare
 * @var array|Antrag[] $neueste_antraege
 * @var array|Aenderungsantrag[] $neueste_aenderungsantraege
 * @var Sprache $sprache
 */



$html = "<form class='form-search well hidden-phone' action='/site/suche/' method='GET'><input type='hidden' name='id' value='" . $veranstaltung->id . "'><div class='nav-list'><div class='nav-header'>" . $sprache->get("Suche") . "</div>";
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
		$html .= ">" . CHtml::link($ant["name"], "/antrag/anzeige/?id=" . $ant["id"]) . "</li>\n";
	}
	$html .= "</ul></div>";
	$this->menus_html[] = $html;

	if ($veranstaltung->darfEroeffnenAntrag()) {
		$this->menus_html[] = '<a class="neuer-antrag" href="/antrag/neu/?veranstaltung=' . $veranstaltung->id . '">
<img alt="Neuen Antrag stellen" src="/css/img/neuer-antrag.png">
</a>';
	}
}


$html = "<div class='well'><ul class='nav nav-list neue-aenderungsantraege'><li class='nav-header'>" . $sprache->get("Neue Änderungsanträge") . "</li>";
if (count($neueste_aenderungsantraege) == 0) $html .= "<li><i>keine</i></li>";
else foreach ($neueste_aenderungsantraege as $ant) {
	$html .= "<li class='aeantrag'>" . CHtml::link("<strong>" . CHtml::encode($ant["revision_name"]) . "</strong> zu " . CHtml::encode($ant->antrag->revision_name), "/aenderungsantrag/anzeige/?id=" . $ant["id"]) . "</li>\n";
}
$html .= "</ul></div>";
$this->menus_html[] = $html;

if ($veranstaltung->typ == Veranstaltung::$TYP_PROGRAMM) {
	if ($veranstaltung->darfEroeffnenAntrag()) {
		$this->menus_html[] = '<a class="neuer-antrag" href="/antrag/neu/?veranstaltung=' . $veranstaltung->id . '">
<img alt="Neuen Antrag stellen" src="/css/img/neuer-antrag.png">
</a>';
	}
}

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

if ($veranstaltung->typ != Veranstaltung::$TYP_PROGRAMM) $html .= "<li class='feed'><a href='/site/feedAntraege/?id=" . $veranstaltung->id . "'>" . $sprache->get("Anträge") . "</a></li>";
$html .= "<li class='feed'><a href='/site/feedAenderungsantraege/?id=" . $veranstaltung->id . "'>" . $sprache->get("Änderungsanträge") . "</a></li>";
$html .= "<li class='feed'><a href='/site/feedKommentare/?id=" . $veranstaltung->id . "'>Kommentare</a></li>";
$html .= "<li class='feed'><a href='/site/feedAlles/?id=" . $veranstaltung->id . "'><b>Alles</b></a></li>";
$html .= "</ul></div>";

$this->menus_html[] = $html;
