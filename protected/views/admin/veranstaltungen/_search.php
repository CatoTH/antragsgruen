<div class="wide form">

	<?php
	/* @var $this VeranstaltungenController */
	/* @var $form GxActiveForm */
	/* @var $model Veranstaltung */

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
		<?php echo $form->label($model, 'name'); ?>
		<?php echo $form->textField($model, 'name', array('maxlength' => 200)); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'datum_von'); ?>
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
		; ?>
	</div>

	<div>
		<?php echo $form->label($model, 'datum_bis'); ?>
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
		; ?>
	</div>

	<div class="row buttons">
		<?php echo GxHtml::submitButton(Yii::t('app', 'Search')); ?>
	</div>

	<?php $this->endWidget(); ?>

</div><!-- search-form -->
