<div class="form">


<?php
/* @var $this AenderungsantragController */
/* @var $form GxActiveForm */
/* @var $model Aenderungsantrag */

$form = $this->beginWidget('GxActiveForm', array(
	'id'                   => 'aenderungsantrag-form',
	'enableAjaxValidation' => true,
));

?>

<p class="note">
	<?php echo Yii::t('app', 'Fields with'); ?> <span class="required">*</span> <?php echo Yii::t('app', 'are required'); ?>.
</p>

<?php echo $form->errorSummary($model); ?>

<div>
	<?php echo $form->labelEx($model, 'antrag_id'); ?>
	<?php echo $form->dropDownList($model, 'antrag_id', GxHtml::listDataEx(Antrag::model()->findAllAttributes(null, true))); ?>
	<?php echo $form->error($model, 'antrag_id'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'revision_name'); ?>
	<?php echo $form->textField($model, 'revision_name'); ?>
	<?php echo $form->error($model, 'revision_name'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'name_neu'); ?>
	<?php echo $form->textField($model, 'name_neu'); ?>
	<?php echo $form->error($model, 'name_neu'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'text_neu'); ?>
	<?php echo $form->textArea($model, 'text_neu'); ?>
	<?php echo $form->error($model, 'text_neu'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'begruendung_neu'); ?>
	<?php echo $form->textArea($model, 'begruendung_neu'); ?>
	<?php echo $form->error($model, 'begruendung_neu'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'aenderung_text'); ?>
	<?php echo $form->textArea($model, 'aenderung_text'); ?>
	<?php echo $form->error($model, 'aenderung_text'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'aenderung_begruendung'); ?>
	<?php echo $form->textArea($model, 'aenderung_begruendung'); ?>
	<?php echo $form->error($model, 'aenderung_begruendung'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'datum_einreichung'); ?>
	<?php $form->widget('ext.datetimepicker.EDateTimePicker', array(
	'model'     => $model,
	'attribute' => "datum_einreichung",
	'options'   => array(
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
	'options'   => array(
		'dateFormat' => 'yy-mm-dd',
	),
));
	?>
	<?php echo $form->error($model, 'datum_beschluss'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'status'); ?>
	<?php echo $form->dropDownList($model, 'status', Aenderungsantrag::$STATI); ?>
	<?php echo $form->textField($model, 'status_string', array('maxlength' => 55)); ?>
	<?php echo $form->error($model, 'status'); ?>
</div>
<!-- row -->

<div style="overflow: auto;">
    <label
            style="float: left;"><?php echo GxHtml::encode($model->getRelationLabel('aenderungsantragKommentare')); ?></label>

    <div style="float: left;">
		<?php
		$kommentare = AenderungsantragKommentar::model()->findAllAttributes(null, true);
		if (count($kommentare) == 0) echo "<em>keine</em>";
		else echo $form->checkBoxList($model, 'aenderungsantragKommentare', GxHtml::encodeEx(GxHtml::listDataEx($kommentare), false, true));
		?>
    </div>
</div>

<div style="overflow: auto;">
    <label
            style="float: left;"><?php echo GxHtml::encode($model->getRelationLabel('aenderungsantragUnterstuetzer')); ?></label>

    <div style="float: left;">
		<?php
		echo UnterstuetzerWidget::printUnterstuetzerWidget($model, "aenderungsantragUnterstuetzer");
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