<div class="wide form well">

	<?php
	/* @var $this TexteController */
	/* @var $form GxActiveForm */
	/* @var $model Texte */

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
		<?php echo $form->label($model, 'text_id'); ?>
		<?php echo $form->textField($model, 'text_id', array('maxlength' => 20)); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'veranstaltung_id'); ?>
		<?php echo $form->dropDownList($model, 'veranstaltung_id', GxHtml::listDataEx(Veranstaltung::model()->findAllAttributes(null, true)), array('prompt' => Yii::t('app', 'All'))); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'text'); ?>
		<?php echo $form->textArea($model, 'text'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'edit_datum'); ?>
		<?php echo $form->textField($model, 'edit_datum'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'edit_person'); ?>
		<?php echo $form->dropDownList($model, 'edit_person', GxHtml::listDataEx(Person::model()->findAllAttributes(null, true)), array('prompt' => Yii::t('app', 'All'))); ?>
	</div>

	<div class="row buttons">
		<?php echo GxHtml::submitButton(Yii::t('app', 'Search')); ?>
	</div>

	<?php $this->endWidget(); ?>

</div><!-- search-form -->
