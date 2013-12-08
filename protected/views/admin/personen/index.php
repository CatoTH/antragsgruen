<?php
/* @var $this PersonenController */
/* @var $model Person */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	Person::label(2),
);

$this->menu = array(
	array('label' => Person::label() . ' ' . Yii::t('app', 'Create'), 'url' => array('create'), "icon" => "plus-sign"),
	array('label' => "Durchsuchen", 'url' => array('admin'), "icon" => "th-list"),
);
?>

	<h1><?php echo GxHtml::encode(Person::label(2)); ?></h1>

<?php

$this->widget('bootstrap.widgets.TbGridView', array(
	'type'         => 'striped bordered condensed',
	'dataProvider' => $dataProvider,
	'template'     => "{items}",
	'columns'      => array(
		array('name' => 'id', 'header' => 'ID'),
		array('name' => 'name', 'type' => 'raw', 'value' => 'CHtml::link(CHtml::encode($data->name), array("admin/personen/update", "id" => $data->id))', 'header' => 'Name'),
		array('name' => 'typ', 'type' => 'raw', 'value' => 'CHtml::encode(Person::$TYPEN[$data->typ])', 'header' => 'Typ'),
		array('name' => 'status', 'type' => 'raw', 'value' => 'CHtml::encode(Person::$STATUS[$data->status])', 'header' => 'Status'),
		array(
			'class'       => 'bootstrap.widgets.TbButtonColumn',
			'htmlOptions' => array('style' => 'width: 50px'),
		),
	),
));
