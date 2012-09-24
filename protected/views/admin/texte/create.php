<?php
/* @var $this TexteController */
/* @var $model Texte */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => '/admin',
	$model->label(2)                => array('index'),
	"Anlegen",
);

$this->menu = array(
	array('label'=> $model->label(2) . ': ' . Yii::t('app', 'List'), 'url' => array('index'), "icon" => "home"),
	array('label'=> $model->label(2) . ' ' . Yii::t('app', 'Manage'), 'url' => array('admin'), "icon" => "th-list"),
);
?>

<h1 class="well"><?php echo GxHtml::encode($model->label()) . ' ' . Yii::t('app', 'Create'); ?></h1>

<div class="well well_first">
	<?php
	$this->renderPartial('_form', array(
		'model'   => $model,
		'buttons' => 'create'));
	?>
</div>