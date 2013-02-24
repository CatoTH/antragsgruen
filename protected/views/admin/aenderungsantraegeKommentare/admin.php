<?php
/* @var $this AenderungsantraegeKommentareController */
/* @var $model AenderungsantragKommentar */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	$model->label(2)                => array('index'),
	Yii::t('app', 'Manage'),
);

$this->menu = array(
	array('label'=> $model->label(2), 'url'=> array('index'), "icon" => "home"),
	array('label'=> "Kommentar " . Yii::t('app', 'Create'), 'url'=> array('create'), "icon" => "plus-sign"),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('aenderungsantrag-kommentar-grid', {
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
	'id'           => 'aenderungsantrag-kommentar-grid',
	'dataProvider' => $model->search(),
	'filter'       => $model,
	'columns'      => array(
		'id',
		array(
			'name'  => 'verfasser_id',
			'value' => 'GxHtml::valueEx($data->verfasser)',
			'filter'=> GxHtml::listDataEx(Person::model()->findAllAttributes("name", true), "id", "name"),
		),
		array(
			'name'  => 'aenderungsantrag_id',
			'value' => 'GxHtml::valueEx($data->aenderungsantrag)',
			'filter'=> GxHtml::listDataEx(Aenderungsantrag::model()->findAllAttributes("id", true), "id", "revision_name"),
		),
		'text',
		'datum',
		'status',
		array(
			'class' => 'CButtonColumn',
		),
	),
)); ?>