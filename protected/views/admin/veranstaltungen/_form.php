<div class="form">


<?php
/* @var $this VeranstaltungenController */
/* @var $form GxActiveForm */
/* @var $model Veranstaltung */

$form = $this->beginWidget('GxActiveForm', array(
	'id'                   => 'veranstaltung-form',
	'enableAjaxValidation' => true,
));

?>

<p class="note">
	<?php echo Yii::t('app', 'Fields with'); ?> <span class="required">*</span> <?php echo Yii::t('app', 'are required'); ?>.
</p>

<?php echo $form->errorSummary($model); ?>

<div>
	<?php echo $form->labelEx($model, 'typ'); ?>
	<?php echo $form->dropDownList($model, 'typ', Veranstaltung::$TYPEN); ?>
	<?php echo $form->error($model, 'typ'); ?>
</div>
<div>
	<?php echo $form->labelEx($model, 'name'); ?>
	<?php echo $form->textField($model, 'name', array('maxlength' => 200)); ?>
	<?php echo $form->error($model, 'name'); ?>
</div>
<div>
	<?php echo $form->labelEx($model, 'name_kurz'); ?>
	<?php echo $form->textField($model, 'name_kurz', array('maxlength' => 45)); ?>
	<?php echo $form->error($model, 'name_kurz'); ?>
</div>
<div>
	<?php echo $form->labelEx($model, 'antrag_einleitung'); ?>
    <div style="display: inline-block; width: 420px;">
		<?php echo $form->textArea($model, 'antrag_einleitung'); ?>
    </div>
	<?php echo $form->error($model, 'antrag_einleitung'); ?>
</div>

<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'datum_von'); ?>
	<?php $form->widget('zii.widgets.jui.CJuiDatePicker', array(
	'model'     => $model,
	'attribute' => 'datum_von',
	'value'     => $model->datum_von,
	'options'   => array(
		'showButtonPanel' => true,
		'changeYear'      => true,
		'dateFormat'      => 'yy-mm-dd',
	),
));
	?>
	<?php echo $form->error($model, 'datum_von'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'datum_bis'); ?>
	<?php $form->widget('zii.widgets.jui.CJuiDatePicker', array(
	'model'     => $model,
	'attribute' => 'datum_bis',
	'value'     => $model->datum_bis,
	'options'   => array(
		'showButtonPanel' => true,
		'changeYear'      => true,
		'dateFormat'      => 'yy-mm-dd',
	),
));
	?>
	<?php echo $form->error($model, 'datum_bis'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'antragsschluss'); ?>
	<?php $form->widget('ext.datetimepicker.EDateTimePicker', array(
	'model'     => $model,
	'attribute' => "antragsschluss",
	'options'   => array(
		'dateFormat' => 'yy-mm-dd',
	),
));
	?>
	<?php echo $form->error($model, 'antragsschluss'); ?>
</div>

<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'policy_antraege'); ?>
	<?php echo $form->dropDownList($model, 'policy_antraege', IPolicyAntraege::getAllInstances()); ?>
	<?php echo $form->error($model, 'policy_antraege'); ?>
</div>
<div>
	<?php echo $form->labelEx($model, 'policy_aenderungsantraege'); ?>
	<?php echo $form->dropDownList($model, 'policy_aenderungsantraege', Veranstaltung::$POLICIES); ?>
	<?php echo $form->error($model, 'policy_aenderungsantraege'); ?>
</div>
<div>
	<?php echo $form->labelEx($model, 'policy_kommentare'); ?>
	<?php echo $form->dropDownList($model, 'policy_kommentare', Veranstaltung::$POLICIES); ?>
	<?php echo $form->error($model, 'policy_kommentare'); ?>
</div>
<div>
	<?php echo $form->labelEx($model, 'admin_email'); ?>
	<?php echo $form->textField($model, 'admin_email', array('maxlength' => 150)); ?>
	<?php echo $form->error($model, 'admin_email'); ?>
</div>
<div>
	<?php echo $form->labelEx($model, 'freischaltung_antraege'); ?>
	<label style="display: inline;"><input type="radio" name="Veranstaltung[freischaltung_antraege]" value="0" <?php if ($model->freischaltung_antraege != 1) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
	<label style="display: inline;"><input type="radio" name="Veranstaltung[freischaltung_antraege]" value="1" <?php if ($model->freischaltung_antraege == 1) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
</div>
<div>
	<?php echo $form->labelEx($model, 'freischaltung_aenderungsantraege'); ?>
	<label style="display: inline;"><input type="radio" name="Veranstaltung[freischaltung_aenderungsantraege]" value="0" <?php if ($model->freischaltung_aenderungsantraege != 1) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
	<label style="display: inline;"><input type="radio" name="Veranstaltung[freischaltung_aenderungsantraege]" value="1" <?php if ($model->freischaltung_aenderungsantraege == 1) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
</div>
<div>
	<?php echo $form->labelEx($model, 'freischaltung_kommentare'); ?> <small>(noch nicht implementiert)</small>
	<label style="display: inline;"><input type="radio" name="Veranstaltung[freischaltung_kommentare]" value="0" <?php if ($model->freischaltung_kommentare != 1) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
	<label style="display: inline;"><input type="radio" name="Veranstaltung[freischaltung_kommentare]" value="1" <?php if ($model->freischaltung_kommentare == 1) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
</div>
<div class="saveholder">
	<?php
	echo GxHtml::submitButton(Yii::t('app', 'Save'), array("class" => "btn btn-primary"));
	$this->endWidget();
	?>
</div>
</div><!-- form -->