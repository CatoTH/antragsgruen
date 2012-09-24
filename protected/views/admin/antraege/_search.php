<div class="wide form well">

<?php
/* @var $this AntragController */
/* @var $form GxActiveForm */
/* @var $model Antrag */

$form = $this->beginWidget('GxActiveForm', array(
	'action' => Yii::app()->createUrl($this->route),
	'method' => 'get',
));
?>

	<div>
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id', array('maxlength' => 10)); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'veranstaltung'); ?>
		<?php echo $form->dropDownList($model, 'veranstaltung', GxHtml::listDataEx(Veranstaltung::model()->findAllAttributes(null, true)), array('prompt' => Yii::t('app', 'All'))); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'abgeleitet_von'); ?>
		<?php echo $form->dropDownList($model, 'abgeleitet_von', GxHtml::listDataEx(Antrag::model()->findAllAttributes(null, true)), array('prompt' => Yii::t('app', 'All'))); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'typ'); ?>
		<?php echo $form->textField($model, 'typ'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'name'); ?>
		<?php echo $form->textArea($model, 'name'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'revision_name'); ?>
		<?php echo $form->textField($model, 'revision_name', array('maxlength' => 50)); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'datum_einreichung'); ?>
		<?php echo $form->textField($model, 'datum_einreichung'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'datum_beschluss'); ?>
		<?php echo $form->textField($model, 'datum_beschluss', array('maxlength' => 45)); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'text'); ?>
		<?php echo $form->textArea($model, 'text'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'begruendung'); ?>
		<?php echo $form->textArea($model, 'begruendung'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'status'); ?>
		<?php echo $form->textField($model, 'status'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'status_string'); ?>
		<?php echo $form->textField($model, 'status_string', array('maxlength' => 55)); ?>
	</div>

	<div class="row buttons">
		<?php echo GxHtml::submitButton(Yii::t('app', 'Search')); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->
