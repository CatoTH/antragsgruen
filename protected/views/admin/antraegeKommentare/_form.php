<div class="form">


	<?php
	/* @var $this AntraegeKommentareController */
	/* @var $form GxActiveForm */
	/* @var $model AntragKommentar */

	$form = $this->beginWidget('GxActiveForm', array(
		'id'                   => 'antrag-kommentar-form',
		'enableAjaxValidation' => true,
	));

	?>

    <p class="note">
		<?php echo Yii::t('app', 'Fields with'); ?> <span class="required">*</span> <?php echo Yii::t('app', 'are required'); ?>.
    </p>

	<?php echo $form->errorSummary($model); ?>

    <div>
		<?php echo $form->labelEx($model, 'verfasserIn_id'); ?>
		<?php echo $form->dropDownList($model, 'verfasserIn_id', GxHtml::listDataEx(Person::model()->findAllAttributes("name", true), "id", "name")); ?>
		<?php echo $form->error($model, 'verfasserIn_id'); ?>
    </div>
    <!-- row -->
    <div>
		<?php echo $form->labelEx($model, 'antrag_id'); ?>
		<?php echo $form->dropDownList($model, 'antrag_id', GxHtml::listDataEx(Antrag::model()->findAllAttributes("name", true), "id", "name")); ?>
		<?php echo $form->error($model, 'antrag_id'); ?>
    </div>
    <!-- row -->
    <div>
		<?php echo $form->labelEx($model, 'absatz'); ?>
		<?php echo $form->textField($model, 'absatz'); ?>
		<?php echo $form->error($model, 'absatz'); ?>
    </div>
    <!-- row -->
    <div>
		<?php echo $form->labelEx($model, 'text'); ?>
		<?php echo $form->textArea($model, 'text'); ?>
		<?php echo $form->error($model, 'text'); ?>
    </div>
    <!-- row -->
    <div>
		<?php echo $form->labelEx($model, 'datum'); ?>
		<?php $form->widget('ext.datetimepicker.EDateTimePicker', array(
		'model'     => $model,
		'attribute' => "datum",
		'options'   => array(
			'dateFormat' => 'yy-mm-dd',
		),
	));
		?>
		<?php echo $form->error($model, 'datum'); ?>
    </div>
    <!-- row -->
    <div>
		<?php echo $form->labelEx($model, 'status'); ?>
		<?php echo $form->dropDownList($model, 'status', AntragKommentar::$STATI); ?>
		<?php echo $form->error($model, 'status'); ?>
    </div>
    <!-- row -->


    <div class="saveholder">
		<?php
		echo GxHtml::submitButton(Yii::t('app', 'Save'), array("class" => "btn btn-primary"));
		$this->endWidget();
		?>
    </div>
</div><!-- form -->