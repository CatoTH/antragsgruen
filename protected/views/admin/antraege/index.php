<?php
/* @var $this AntraegeController */
/* @var $model Antrag */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => '/admin',
	Antrag::label(2),
);

$this->menu = array(
	array('label'=> Antrag::label() . ' ' . Yii::t('app', 'Create'), 'url' => array('create'), "icon" => "plus-sign"),
	array('label'=> "Durchsuchen", 'url' => array('admin'), "icon" => "th-list"),
);
?>

<div class="well well_first">
	<h1><?php echo GxHtml::encode(Antrag::label(2)); ?></h1>

	<?php
	$dataProvider->sort->defaultOrder = "datum_einreichung DESC";
	$dataProvider->criteria->condition = "status != " . IAntrag::$STATUS_GELOESCHT;
	$this->widget('zii.widgets.CListView', array(
	'dataProvider'=> $dataProvider,
	'itemView'    => '_list',
));
	?>
</div>