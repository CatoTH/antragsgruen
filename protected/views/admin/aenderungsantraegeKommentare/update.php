<?php
/* @var $this AenderungsantraegeKommentareController */
/* @var $model AenderungsantragKommentar */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => '/admin',
	$model->label(2)                => array('index'),
	Yii::t('app', 'Update'),
);

$komm_link = $this->createUrl("aenderungsantrag/anzeige", array(
	"veranstaltung_id" => $model->aenderungsantrag->antrag->veranstaltung0->yii_url,
	"antrag_id" => $model->aenderungsantrag->antrag->id,
	"aenderungsantrag_id" => $model->aenderungsantrag->id,
	"kommentar_id" => $model->id, "#" => "komm" . $model->id)
);
$this->menu = array(
	array('label' => $model->label(2), 'url'=> array('index'), "icon" => "home"),
	array('label' => $model->label() . ' ' . Yii::t('app', 'Create'), 'url'=> array('create'), "icon" => "plus-sign"),
	array('label' => $model->label() . ' ' . Yii::t('app', 'View'), 'url'=> $komm_link, "icon" => "eye-open"),
	array('label'=> $model->label() . ' ' . Yii::t('app', 'Delete'), 'url'=> '#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 'confirm'=> 'Are you sure you want to delete this item?'), "icon" => "remove"),
	array('label' => "Durchsuchen", 'url'=> array('admin'), "icon" => "th-sign"),
);
?>

<div class="well">
	<h1 class="well"><?php echo Yii::t('app', 'Update') . ': ' . GxHtml::encode($model->label()) . ' ' . GxHtml::encode(GxHtml::valueEx($model)); ?></h1>

	<?php
	$this->renderPartial('_form', array(
		'model' => $model));
	?>
</div>