<?php
/* @var $this TexteController */
/* @var $model Texte */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	$model->label(2)                => array('index'),
	GxHtml::valueEx($model),
);

$this->menu = array(
	array('label'=> $model->label(2) . ': ' . Yii::t('app', 'List'), 'url'=> array('index'), "icon" => "home"),
	array('label'=> $model->label() . ' ' . Yii::t('app', 'Create'), 'url'=> array('create'), "icon" => "plus-sign"),
	array('label'=> $model->label() . ' ' . Yii::t('app', 'Update'), 'url'=> array('update', 'id' => $model->id), "icon" => "edit"),
	array('label'=> $model->label() . ' ' . Yii::t('app', 'Delete'), 'url'=> '#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 'confirm'=> 'Are you sure you want to delete this item?'), "icon" => "remove"),
	array('label'=> $model->label(2) . ' ' . Yii::t('app', 'Manage'), 'url'=> array('admin'), "icon" => "th-list"),
);
?>

<h1 class="well"><?php echo Yii::t('app', 'View') . ': ' . GxHtml::encode($model->label()) . ' ' . GxHtml::encode(GxHtml::valueEx($model)); ?></h1>

<div class="well well_first">
	<?php $this->widget('zii.widgets.CDetailView', array(
	'data'       => $model,
	'attributes' => array(
		'id',
		'text_id',
		array(
			'name'  => 'veranstaltung',
			'type'  => 'raw',
			'value' => $model->veranstaltung !== null ? GxHtml::encode(GxHtml::valueEx($model->veranstaltung)) : null,
		),
		'text',
		'edit_datum',
		array(
			'name'  => 'editPerson',
			'type'  => 'raw',
			'value' => $model->editPerson !== null ? GxHtml::encode($model->editPerson->name) : null,
		),
	),
)); ?>

</div>