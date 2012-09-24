<?php
/* @var $this AenderungsantraegeController */
/* @var $model Aenderungsantrag */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => '/admin',
	Aenderungsantrag::label(2),
);

$this->menu = array(
	array('label'=> Aenderungsantrag::label() . ' ' . Yii::t('app', 'Create'), 'url' => array('create'), "icon" => "plus-sign"),
	array('label'=> "Durchsuchen", 'url' => array('admin'), "icon" => "th-list"),
);
?>

<h1 class="well"><?php echo GxHtml::encode(Aenderungsantrag::label(2)); ?></h1>

<div class="well">
	<?php
	$dataProvider->criteria->condition = "status != " . IAntrag::$STATUS_GELOESCHT;
	$dataProvider->sort->defaultOrder = "datum_einreichung DESC";
	$this->widget('zii.widgets.CListView', array(
		'dataProvider'=> $dataProvider,
		'itemView'    => '_list',
	));
	?></div>