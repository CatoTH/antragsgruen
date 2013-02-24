<?php
/* @var $this AenderungsantraegeKommentareController */
/* @var $model AenderungsantragKommentar */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	$model->label(2)                => array('index'),
	Yii::t('app', 'Create'),
);

$this->menu = array(
	array('label'=> "Änderungsantrags-Kommentare", 'url' => array('index'), "icon" => "home"),
	array('label'=> "Durchsuchen", 'url' => array('admin'), "icon" => "th-list"),
);
?>

<h1 class="well"><?php echo GxHtml::encode($model->label()) . ' ' . Yii::t('app', 'Create'); ?></h1>

<div class="well">
	<?php
	$this->renderPartial('_form', array(
		'model'   => $model,
		'buttons' => 'create'));
	?>
</div>