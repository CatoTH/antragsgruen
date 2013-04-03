<?php
/* @var $this AenderungsantraegeController */
/* @var $model Aenderungsantrag */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	Aenderungsantrag::label(2),
);

$this->menu = array(
	array('label'=> "Durchsuchen", 'url' => array('admin'), "icon" => "th-list"),
);
?>

<h1 class="well"><?php echo GxHtml::encode(Aenderungsantrag::label(2)); ?></h1>

<div class="well">
	<a style="float: left;" href="<?php echo CHtml::encode($this->createUrl("/admin/aenderungsantraege/index", array("by_ldk" => true)))?>"><span class="icon-chevron-right"></span> Freigeschaltete, sortiert</a>
	<?php
	$dataProvider->criteria->condition = "status != " . IAntrag::$STATUS_GELOESCHT;
	$dataProvider->sort->defaultOrder = "datum_einreichung DESC";
	$this->widget('zii.widgets.CListView', array(
		'dataProvider'=> $dataProvider,
		'itemView'    => '_list',
	));
	?></div>