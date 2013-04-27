<?php
/**
 * @var InfosController $this
 * @var array|Veranstaltungsreihe[] $reihen
 */

$html = "<div class='well'><ul class='nav nav-list einsatzorte-list'>";
$html .= "<li class='nav-header'>Aktuelle Einsatzorte</li>";
foreach ($reihen as $reihe) {
	$html .= "<li>" . CHtml::link($reihe->name, $this->createUrl("veranstaltung/index", array("veranstaltungsreihe_id" => $reihe->subdomain))) . "</li>\n";
}
if (defined("ANTRAGSGRUEN_LEGACY_LINKS") && ANTRAGSGRUEN_LEGACY_LINKS) {
	$html .= "<li><a href='http://www.konzepte-fuer-hessen.de'>LV Hessen: Programm Landtagswahlen '13</a></li>\n";
	$html .= "<li><a href='http://ltwby13-programm.antragsgruen.de'>LV Bayern: Programm Landtagswahlen '13</a></li>\n";
	$html .= "<li><a href='http://muenchen-2014.antragsgruen.de'>KV München: Programm Kommunalwahlen '14</a></li>\n";
	$html .= "<li><a href='http://ldk-berlin-2013.antragsgruen.de'>LV Berlin: LDK '13 Anträge</a></li>\n";
	$html .= "<li><a href='http://www.transparenz-mv.de/'>Fraktion Mecklemburg-Vorpommern: Entwurf d. Transparenzgesetzes</a></li>\n";
}
$html .= '</ul></div>';
$this->menus_html[] = $html;
