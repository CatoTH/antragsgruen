<?php
/* @var $this AntraegeController */
/* @var $model Antrag */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	Antrag::label(2),
);

$this->menu = array(
	array('label' => "Durchsuchen", 'url' => array('admin'), "icon" => "th-list"),
);
?>

	<h1><?php echo GxHtml::encode(Antrag::label(2)); ?></h1>

<?php
$this->widget('zii.widgets.CListView', array(
	'dataProvider' => $dataProvider,
	'itemView'     => '_list',
));
