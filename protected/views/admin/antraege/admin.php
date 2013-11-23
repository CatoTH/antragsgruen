<?php
/* @var $this AntraegeController */
/* @var $model Antrag */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	$model->label(2)                => array('index'),
	"Durchsuchen",
);

$this->menu = array(
	array('label'=> $model->label(2), 'url'=> array('index'), "icon" => "home"),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('antrag-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<h1><?php echo GxHtml::encode($model->label(2)) . ' ' . Yii::t('app', 'Manage'); ?></h1>

<p>
	<?php Yii::t('app', 'You may optionally enter a comparison operator (&lt;, &lt;=, &gt;, &gt;=, &lt;&gt; or =) at the beginning of each of your search values to specify how the comparison should be done.'); ?></p>

<?php echo GxHtml::link(Yii::t('app', 'Advanced Search'), '#', array('class' => 'search-button')); ?>
<div class="search-form">
	<?php $this->renderPartial('_search', array(
	'model' => $model,
)); ?>
</div><!-- search-form -->

<?php $this->widget('zii.widgets.grid.CGridView', array(
	'id'           => 'antrag-grid',
	'dataProvider' => $model->search(),
	'filter'       => $model,
	'columns'      => array(
		'id',
		array(
			'name'  => 'veranstaltung',
			'value' => 'GxHtml::valueEx($data->veranstaltung)',
			'filter'=> GxHtml::listDataEx(Veranstaltung::model()->findAllAttributes(null, true)),
		),
		array(
			'name'  => 'abgeleitet_von',
			'value' => 'GxHtml::valueEx($data->abgeleitetVon)',
			'filter'=> GxHtml::listDataEx(Antrag::model()->findAllAttributes(null, true)),
		),
		'typ',
		'name',
		'revision_name',
		/*
		'datum_einreichung',
		'datum_beschluss',
		'text',
		'begruendung',
		'status',
		'status_string',
		*/
		array(
			'class' => 'CButtonColumn',
			'template'=>'{update}',
		),
	),
)); ?>