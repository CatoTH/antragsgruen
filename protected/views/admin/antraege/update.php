<?php
/* @var $this AntraegeController */
/* @var $model Antrag */
/* @var $messages array */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => '/admin',
	$model->label(2)                => array('index'),
	Yii::t('app', 'Update'),
);

$this->menu = array(
	array('label' => $model->label(2) . ': ' . Yii::t('app', 'List'), 'url'=> array('index'), "icon" => "home"),
	array('label' => $model->label() . ' ' . Yii::t('app', 'Create'), 'url'=> array('create'), "icon" => "plus-sign"),
	array('label' => $model->label() . ' ' . Yii::t('app', 'View'), 'url'=> "/antrag/anzeige/?id=" . $model->id, "icon" => "eye-open"),
	array('label' => $model->label() . ' ' . Yii::t('app', 'Delete'), 'url'=> '#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 'confirm'=> 'Are you sure you want to delete this item?'), "icon" => "remove"),
	array('label' => "Durchsuchen", 'url'=> array('admin'), "icon" => "th-list"),
);
?>

<div class="well">

	<h1><?php echo Yii::t('app', 'Update') . ': ' . GxHtml::encode($model->label()) . ' ' . GxHtml::encode(GxHtml::valueEx($model)); ?></h1>
	<br>

	<?php
	if ($model->status == Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT) {
		$form        = $this->beginWidget('GxActiveForm');
		$new_rev = $model->veranstaltung0->naechsteAntragRevNr($model->typ);

		echo '<input type="hidden" name="' . AntiXSS::createToken("antrag_freischalten") . '" value="' . CHtml::encode($new_rev) . '">';
		echo "<div style='text-align: center;'>";
		$this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'type' => 'primary', 'icon'=> 'ok white', 'label'=> 'Freischalten als ' . $new_rev));
		echo "</div>";
		$this->endWidget();
		echo "<br>";
	}


	if (count($messages) > 0) echo "<strong>" . GxHtml::encode(implode("<br>", $messages)) . "</strong><br><br>";


	$this->renderPartial('_form', array(
		'model' => $model));
	?>
</div>