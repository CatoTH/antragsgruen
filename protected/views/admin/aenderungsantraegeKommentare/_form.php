<div class="form">


	<?php
	/* @var $this AenderungsantraegeKommentareController */
	/* @var $form GxActiveForm */
	/* @var $model AenderungsantragKommentar */

	$form = $this->beginWidget('GxActiveForm', array(
		'id'                   => 'aenderungsantrag-kommentar-form',
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
		<?php echo $form->labelEx($model, 'aenderungsantrag_id'); ?>
		<?php echo $form->dropDownList($model, 'aenderungsantrag_id', GxHtml::listDataEx(Aenderungsantrag::model()->findAllAttributes("revision_name", true), "id", "revision_name")); ?>
		<?php echo $form->error($model, 'aenderungsantrag_id'); ?>
    </div>
    <!-- row -->
    <div>
		<?php echo $form->labelEx($model, 'text'); ?>
		<?php echo $form->textField($model, 'text', array('maxlength' => 45)); ?>
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
		<?php echo $form->dropDownList($model, 'status', AenderungsantragKommentar::$STATI); ?>
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