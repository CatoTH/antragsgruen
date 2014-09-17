<?php
/**
 * @var AntraegeController $this
 * @var CActiveDataProvider $dataProvider
 * @var int[] $anzahl_stati
 * @var int $anzahl_gesamt
 * @var int|null $status_curr
 */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	Antrag::label(2),
);

$this->menu = array(
	array('label' => "Durchsuchen", 'url' => array('admin'), "icon" => "th-list"),
);

echo '<h1>' . GxHtml::encode(Antrag::label(2)) . '</h1>';

if ($anzahl_gesamt > 0) {
	echo '<div>';
	if ($status_curr === null) echo '[<strong>Alle (' . $anzahl_gesamt . ')</strong>]';
	else echo '[' . CHtml::link('Alle (' . $anzahl_gesamt . ')', array('/admin/antraege/index', array())) . ']';

	foreach ($anzahl_stati as $key=>$anz) {
		$name = CHtml::encode(IAntrag::$STATI[$key] . ' (' . $anz . ')');
		if ($status_curr !== null && $status_curr == $key) echo ' - [<strong>' . $name . '</strong>]';
		else echo ' - [' . CHtml::link($name, $this->createUrl("/admin/antraege/index", array("status" => $key))) . ']';
	}
	echo '</div>';
}


$this->widget('zii.widgets.CListView', array(
	'dataProvider' => $dataProvider,
	'itemView'     => '_list',
));
