<?php
/* @var $this AenderungsantraegeController */
/* @var $model Aenderungsantrag */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	$model->label(2)                => array('index'),
	Yii::t('app', 'Create'),
);

$this->menu = array(
	array('label' => $model->label(2), 'url' => array('index'), "icon" => "home"),
	array('label' => "Durchsuchen", 'url' => array('admin'), "icon" => "th-list"),
);
?>

<h1><?php echo GxHtml::encode($model->label()) . ' ' . Yii::t('app', 'Create'); ?></h1>

<?php
$this->renderPartial('_form', array(
	'model'   => $model,
	'buttons' => 'create'));
?>
