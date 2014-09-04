<?php
/**
 * @var InfosController $this
 * @var array|Veranstaltungsreihe[] $reihen
 */

$html = "<ul class='nav nav-list einsatzorte-list'>";
$html .= "<li class='nav-header'>Aktuelle Einsatzorte</li>";
foreach ($reihen as $reihe) {
	$html .= "<li>" . CHtml::link($reihe->name, $this->createUrl("veranstaltung/index", array("veranstaltungsreihe_id" => $reihe->subdomain))) . "</li>\n";
}
$html .= '</ul>';
$this->menus_html[] = $html;
