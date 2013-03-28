<div class="wide form well">

<?php
/* @var $this AenderungsantraegeKommentareController */
/* @var $form GxActiveForm */
/* @var $model AenderungsantragKommentar */

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
		<?php echo $form->label($model, 'aenderungsantrag_id'); ?>
		<?php echo $form->dropDownList($model, 'aenderungsantrag_id', GxHtml::listDataEx(Aenderungsantrag::model()->findAllAttributes("name", true), "id", "name"), array('prompt' => Yii::t('app', 'All'))); ?>
	</div>

	<div>
		<?php echo $form->label($model, 'text'); ?>
		<?php echo $form->textField($model, 'text', array('maxlength' => 45)); ?>
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
