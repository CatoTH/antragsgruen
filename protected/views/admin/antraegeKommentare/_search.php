<div class="wide form well">

	<?php
	/* @var $this AntraegeKommentareController */
	/* @var $form GxActiveForm */
	/* @var $model AntragKommentar */

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
		<?php echo $form->label($model, 'verfasserIn_id'); ?>
		<?php echo $form->dropDownList($model, 'verfasserIn_id', GxHtml::listDataEx(Person::model()->findAllAttributes("name", true), "id", "name"), array('prompt' => Yii::t('app', 'All'))); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'antrag_id'); ?>
		<?php echo $form->dropDownList($model, 'antrag_id', GxHtml::listDataEx(Antrag::model()->findAllAttributes("name", true), "id", "name"), array('prompt' => Yii::t('app', 'All'))); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'absatz'); ?>
		<?php echo $form->textField($model, 'absatz'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'text'); ?>
		<?php echo $form->textArea($model, 'text'); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'datum'); ?>
		<?php echo $form->textField($model, 'datum'); ?>
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
