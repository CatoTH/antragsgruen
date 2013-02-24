<?php
/* @var $this TexteController */
/* @var $model Texte */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	$model->label(2) => array('index'),
	"Durchsuchen",
);

$this->menu = array(
		array('label'=> $model->label(2) . ' ' . Yii::t('app', 'List'), 'url'=>array('index'), "icon" => "home"),
		array('label'=> $model->label() . ' ' . Yii::t('app', 'Create'), 'url'=>array('create'), "icon" => "plus-sign"),
	);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('texte-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<h1 class="well"><?php echo GxHtml::encode($model->label(2)) . ' ' . Yii::t('app', 'Manage'); ?></h1>

<p>
<?php Yii::t('app', 'You may optionally enter a comparison operator (&lt;, &lt;=, &gt;, &gt;=, &lt;&gt; or =) at the beginning of each of your search values to specify how the comparison should be done.'); ?></p>

<?php echo GxHtml::link(Yii::t('app', 'Advanced Search'), '#', array('class' => 'search-button')); ?>
<div class="search-form">
<?php $this->renderPartial('_search', array(
	'model' => $model,
)); ?>
</div><!-- search-form -->

<?php $this->widget('zii.widgets.grid.CGridView', array(
	'id' => 'texte-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		'id',
		'text_id',
		array(
				'name'=>'veranstaltung_id',
				'value'=>'GxHtml::valueEx($data->veranstaltung)',
				'filter'=>GxHtml::listDataEx(Veranstaltung::model()->findAllAttributes(null, true)),
				),
		'text',
		'edit_datum',
		array(
				'name'=>'edit_person',
				'value'=>'GxHtml::valueEx($data->editPerson)',
				'filter'=>GxHtml::listDataEx(Person::model()->findAllAttributes(null, true)),
				),
		array(
			'class' => 'CButtonColumn',
		),
	),
)); ?>