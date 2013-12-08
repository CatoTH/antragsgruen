<div class="wide form">

	<?php
	/* @var $this PersonenController */
	/* @var $form GxActiveForm */
	/* @var $model Person */

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
		<?php echo $form->label($model, 'typ'); ?>
		<?php echo $form->textField($model, 'typ', array('maxlength' => 12)); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'name'); ?>
		<?php echo $form->textField($model, 'name', array('maxlength' => 100)); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'email'); ?>
		<?php echo $form->textField($model, 'email', array('maxlength' => 200)); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'telefon'); ?>
		<?php echo $form->textField($model, 'telefon', array('maxlength' => 100)); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'auth'); ?>
		<?php echo $form->textField($model, 'auth', array('maxlength' => 200)); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'angelegt_datum'); ?>
		<?php echo $form->textField($model, 'angelegt_datum'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'admin'); ?>
		<?php echo $form->textField($model, 'admin'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'status'); ?>
		<?php echo $form->textField($model, 'status'); ?>
	</div>

	<div class="row buttons">
		<?php echo GxHtml::submitButton(Yii::t('app', 'Search')); ?>
	</div>

	<?php $this->endWidget(); ?>

</div><!-- search-form -->
