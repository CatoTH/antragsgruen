<div class="form">


<?php
Yii::app()->getClientScript()->registerScriptFile(Yii::app()->request->baseUrl . '/js/ckeditor/ckeditor.js');
Yii::app()->getClientScript()->registerScriptFile(Yii::app()->request->baseUrl . '/js/ckeditor.bbcode.js');

/**
 * @var AntraegeController $this
 * @var $form GxActiveForm
 * @var $model Antrag
 * @var $this AntraegeController
 */

$form = $this->beginWidget('GxActiveForm', array(
	'id'                   => 'antrag-form',
	'enableAjaxValidation' => true,
));
?>

<?php echo $form->errorSummary($model); ?>

<div>
	<?php echo $form->labelEx($model, 'veranstaltung'); ?>
	<?php echo $form->dropDownList($model, 'veranstaltung', GxHtml::listDataEx(Veranstaltung::model()->findAllAttributes(null, true))); ?>
	<?php echo $form->error($model, 'veranstaltung'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'abgeleitet_von'); ?>
	<?php echo $form->dropDownList($model, 'abgeleitet_von',
	GxHtml::listDataEx(Antrag::model()->findAllAttributes(null, true)),
	array("empty" => "-")
); ?>
	<?php echo $form->error($model, 'abgeleitet_von'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'typ'); ?>
	<?php echo $form->dropDownList($model, "typ", Antrag::$TYPEN); ?>
	<?php echo $form->error($model, 'typ'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'status'); ?>
	<?php echo $form->dropDownList($model, 'status', IAntrag::$STATI); ?>
	<?php echo $form->textField($model, 'status_string', array('maxlength' => 55)); ?>
	<?php echo $form->error($model, 'status'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'name'); ?>
	<?php echo $form->textField($model, 'name'); ?>
	<?php echo $form->error($model, 'name'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'revision_name'); ?>
	<?php echo $form->textField($model, 'revision_name', array('maxlength' => 20)); ?>
	<?php echo $form->error($model, 'revision_name'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'datum_einreichung'); ?>
	<?php $form->widget('ext.datetimepicker.EDateTimePicker', array(
	'model'     => $model,
	'attribute' => "datum_einreichung",
	'options' => array(
		'dateFormat' => 'yy-mm-dd',
	),
));
	?>
	<?php echo $form->error($model, 'datum_einreichung'); ?>
</div>

<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'datum_beschluss'); ?>
	<?php $form->widget('ext.datetimepicker.EDateTimePicker', array(
	'model'     => $model,
	'attribute' => "datum_beschluss",
	'options' => array(
		'dateFormat' => 'yy-mm-dd',
	),
));
	?>
	<?php echo $form->error($model, 'datum_beschluss'); ?>
</div>

<!-- row -->

<div>
	<?php echo $form->labelEx($model, 'text'); ?>
	<div style="display: inline-block; width: 420px;">
		<?php echo $form->textArea($model, 'text'); ?>
	</div>
	<?php echo $form->error($model, 'text'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'begruendung'); ?>
	<div style="display: inline-block; width: 420px;">
		<?php echo $form->textArea($model, 'begruendung'); ?>
	</div>
	<?php echo $form->error($model, 'begruendung'); ?>
</div>
<!-- row -->

<script>
	$(function () {
		CKEDITOR.replace('Antrag_text', {'toolbar':'Animexx', 'customConfig':"/js/ckconfig.js", width:510 });
		CKEDITOR.replace('Antrag_begruendung', {'toolbar':'Animexx', 'customConfig':"/js/ckconfig.js", width:510 });
	})
</script>


<div style="overflow: auto;">
	<label style="float: left;"><?php echo GxHtml::encode($model->getRelationLabel('antragUnterstuetzer')); ?></label>

	<div style="float: left;">
		<?php
		echo UnterstuetzerWidget::printUnterstuetzerWidget($model, "antragUnterstuetzer");
		?>
	</div>
</div>
<div class="saveholder">
	<?php
	echo GxHtml::submitButton(Yii::t('app', 'Save'), array("class" => "btn btn-primary"));
	$this->endWidget();
	?>
</div>
</div><!-- form -->