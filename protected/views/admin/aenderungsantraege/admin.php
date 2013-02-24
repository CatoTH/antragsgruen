<?php
/* @var $this AenderungsantraegeController */
/* @var $model Aenderungsantrag */

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
	$.fn.yiiGridView.update('aenderungsantrag-grid', {
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
	'id'           => 'aenderungsantrag-grid',
	'dataProvider' => $model->search(),
	'filter'       => $model,
	'columns'      => array(
		'id',
		array(
			'name'  => 'antrag_id',
			'value' => 'GxHtml::valueEx($data->antrag)',
			'filter'=> GxHtml::listDataEx(Antrag::model()->findAllAttributes("name", true), "id", "name"),
		),
		'text_neu',
		'begruendung_neu',
		'aenderung_text',
		'aenderung_begruendung',
		/*
		'datum_einreichung',
		'datum_beschluss',
		'status',
		'status_string',
		*/
		array(
			'class' => 'CButtonColumn',
		),
	),
)); ?>