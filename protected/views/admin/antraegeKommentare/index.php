<?php
/* @var $this AntraegeKommentareController */
/* @var $model AntragKommentar */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	AntragKommentar::label(2),
	Yii::t('app', 'Index'),
);

$this->menu = array(
	array('label' => AntragKommentar::label() . ' ' . Yii::t('app', 'Create'), 'url' => array('create'), "icon" => "plus-sign"),
	array('label' => "Durchsuchen", 'url' => array('admin'), "icon" => "th-list"),
);
?>

	<h1><?php echo GxHtml::encode(AntragKommentar::label(2)); ?></h1>

<?php
$dataProvider->sort->defaultOrder = "datum DESC";
$this->widget('zii.widgets.CListView', array(
	'dataProvider' => $dataProvider,
	'itemView'     => '_list',
));
