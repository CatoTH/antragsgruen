<div class="wide form well">

<?php
/* @var $this AenderungsantragController */
/* @var $form GxActiveForm */
/** @var AenderungsantragKommentar $model */

$form = $this->beginWidget('GxActiveForm', array(
	'action' => Yii::app()->createUrl($this->route),
	'method' => 'get',
));
?>

	<div>
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'antrag_id'); ?>
		<?php echo $form->dropDownList($model, 'antrag_id', GxHtml::listDataEx(Antrag::model()->findAllAttributes(null, true)), array('prompt' => Yii::t('app', 'All'))); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'text_neu'); ?>
		<?php echo $form->textArea($model, 'text_neu'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'begruendung_neu'); ?>
		<?php echo $form->textArea($model, 'begruendung_neu'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'aenderung_text'); ?>
		<?php echo $form->textArea($model, 'aenderung_text'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'aenderung_begruendung'); ?>
		<?php echo $form->textArea($model, 'aenderung_begruendung'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'datum_einreichung'); ?>
		<?php echo $form->textField($model, 'datum_einreichung'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'datum_beschluss'); ?>
		<?php echo $form->textField($model, 'datum_beschluss'); ?>
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
