<?php
/* @var $this TexteController */
/* @var $model Texte */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	$model->label(2)                => array('index'),
	"Anlegen",
);

$this->menu = array(
	array('label' => $model->label(2) . ': ' . Yii::t('app', 'List'), 'url' => array('index'), "icon" => "home"),
	array('label' => $model->label(2) . ' ' . Yii::t('app', 'Manage'), 'url' => array('admin'), "icon" => "th-list"),
);
?>

	<h1><?php echo GxHtml::encode($model->label()) . ' ' . Yii::t('app', 'Create'); ?></h1>

<?php
$this->renderPartial('_form', array(
	'model'   => $model,
	'buttons' => 'create'));
