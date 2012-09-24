<?php
/* @var $this VeranstaltungenController */
/* @var $model Veranstaltung */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => '/admin',
	"Veranstaltungen"
);

$this->menu = array(
	array('label'=> Veranstaltung::label() . ' ' . Yii::t('app', 'Create'), 'url' => array('create'), "icon" => "plus-sign"),
	array('label'=> "Durchsuchen", 'url' => array('admin'), "icon" => "th-list"),
);
?>

<h1 class="well"><?php echo GxHtml::encode(Veranstaltung::label(2)); ?></h1>

<div class="well well_first">
	<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=> $dataProvider,
	'itemView'    => '_list',
));
	?>
</div>