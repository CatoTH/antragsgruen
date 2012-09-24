<?php
/* @var $this AntraegeKommentareController */
/* @var $model AntragKommentar */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => '/admin',
	$model->label(2)                => array('index'),
	Yii::t('app', 'Update'),
);

$this->menu = array(
	array('label' => $model->label(2) . ': ' . Yii::t('app', 'List'), 'url'=> array('index'), "icon" => "home"),
	array('label' => $model->label() . ' ' . Yii::t('app', 'Create'), 'url'=> array('create'), "icon" => "plus-sign"),
	array('label' => $model->label() . ' ' . Yii::t('app', 'View'), 'url'=> "/antrag/anzeige/?id=" . $model->antrag_id . "&kommentar=" . $model->id . "#komm" . $model->id, "icon" => "eye-open"),
	array('label'=> $model->label() . ' ' . Yii::t('app', 'Delete'), 'url'=> '#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 'confirm'=> 'Are you sure you want to delete this item?'), "icon" => "remove"),
	array('label' => $model->label(2) . ' ' . Yii::t('app', 'Manage'), 'url'=> array('admin'), "icon" => "th-list"),
);
?>

<div class="well">

	<h1><?php echo Yii::t('app', 'Update') . ': ' . GxHtml::encode($model->label()) . ' ' . GxHtml::encode(GxHtml::valueEx($model)); ?></h1>

	<?php
	$this->renderPartial('_form', array(
		'model' => $model));
	?>
</div>