<?php
/* @var $this TexteController */
/* @var $model Texte */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	Texte::label(2),
);

$this->menu = array(
	array('label' => Texte::label() . ' ' . Yii::t('app', 'Create'), 'url' => array('create'), "icon" => "plus-sign"),
	array('label' => Texte::label(2) . ' ' . Yii::t('app', 'Manage'), 'url' => array('admin'), "icon" => "th-list"),
);
?>

	<h1><?php echo GxHtml::encode(Texte::label(2)); ?></h1>

	<br>
	<strong>Hinweis:</strong> Diese Texte sind nicht für die eigentlichen Anträge / Wahlprogramme gedacht.<br>
	<strong><?php echo CHtml::link("Hier kannst du einen Antrag anlegen.", $this->createUrl("/antrag/neu/")) ?></strong>
	<br><br>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider' => $dataProvider,
	'itemView'     => '_view',
));
