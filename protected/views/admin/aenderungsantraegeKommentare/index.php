<?php
/* @var $this AenderungsantraegeKommentareController */
/* @var $model AenderungsantragKommentar */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	AenderungsantragKommentar::label(2)
);

$this->menu = array(
	array('label' => "Kommentar " . Yii::t('app', 'Create'), 'url' => array('create'), "icon" => "plus-sign"),
	array('label' => "Durchsuchen", 'url' => array('admin'), "icon" => "th-list"),
);
?>

<h1><?php echo GxHtml::encode(AenderungsantragKommentar::label(2)); ?></h1>

<?php
$dataProvider->sort->defaultOrder = "datum DESC";
$this->widget('zii.widgets.CListView', array(
	'dataProvider' => $dataProvider,
	'itemView'     => '_list',
));
