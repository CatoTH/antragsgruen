<?php
/**
 * @var AenderungsantraegeController $this
 * @var CActiveDataProvider $dataProvider
 * @var int[] $anzahl_stati
 * @var int $anzahl_gesamt
 * @var int|null $status_curr
 */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	Aenderungsantrag::label(2),
);

$this->menu = array(
	array('label' => "Durchsuchen", 'url' => array('admin'), "icon" => "th-list"),
);

echo '<h1>' . GxHtml::encode(Aenderungsantrag::label(2)) . '</h1>';

if ($anzahl_gesamt > 0) {
	echo '<div>';
	if ($status_curr === null) echo '[<strong>Alle (' . $anzahl_gesamt . ')</strong>]';
	else echo '[' . CHtml::link('Alle (' . $anzahl_gesamt . ')', array('/admin/aenderungsantraege/index', array())) . ']';

	foreach ($anzahl_stati as $key=>$anz) {
		$name = CHtml::encode(IAntrag::$STATI[$key] . ' (' . $anz . ')');
		if ($status_curr !== null && $status_curr == $key) echo ' - [<strong>' . $name . '</strong>]';
		else echo ' - [' . CHtml::link($name, $this->createUrl("/admin/aenderungsantraege/index", array("status" => $key))) . ']';
	}
	echo '</div>';
}


$dataProvider->criteria->condition = "status != " . IAntrag::$STATUS_GELOESCHT;
$dataProvider->sort->defaultOrder  = "datum_einreichung DESC";
$this->widget('zii.widgets.CListView', array(
	'dataProvider' => $dataProvider,
	'itemView'     => '_list',
));
