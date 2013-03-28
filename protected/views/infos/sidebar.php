<?php
/**
 * @var InfosController $this
 * @var array|Veranstaltungsreihe[] $reihen
 */

$html = "<div class='well'><ul class='nav nav-list'>";
$html .= "<li class='nav-header'>Aktuelle Einsatzorte</li>";
foreach ($reihen as $reihe) {
	$html .= "<li>" . CHtml::link($reihe->name, $this->createUrl("veranstaltung/index", array("veranstaltungsreihe_id" => $reihe->subdomain))) . "</li>\n";
}
$html .= '</ul></div>';
$this->menus_html[] = $html;
