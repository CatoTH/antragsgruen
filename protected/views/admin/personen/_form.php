<div class="form">


	<?php
	/* @var $this PersonenController */
	/* @var $form GxActiveForm */
	/* @var $model Person */

	$form = $this->beginWidget('GxActiveForm', array(
		'id'                   => 'person-form',
		'enableAjaxValidation' => true,
	));

	?>

	<p class="note">
		<?php echo Yii::t('app', 'Fields with'); ?> <span class="required">*</span> <?php echo Yii::t('app', 'are required'); ?>.
	</p>

	<?php echo $form->errorSummary($model); ?>

	<div>
		<?php echo $form->labelEx($model, 'typ'); ?>
		<?php echo $form->dropDownList($model, 'typ', Person::$TYPEN); ?>
		<?php echo $form->error($model, 'typ'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'name'); ?>
		<?php echo $form->textField($model, 'name', array('maxlength' => 100)); ?>
		<?php echo $form->error($model, 'name'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'email'); ?>
		<?php echo $form->textField($model, 'email', array('maxlength' => 200)); ?>
		<?php echo $form->error($model, 'email'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'telefon'); ?>
		<?php echo $form->textField($model, 'telefon', array('maxlength' => 100)); ?>
		<?php echo $form->error($model, 'telefon'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'auth'); ?>
		<?php echo $form->textField($model, 'auth', array('maxlength' => 200)); ?>
		<?php echo $form->error($model, 'auth'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'angelegt_datum'); ?>
		<?php echo $form->textField($model, 'angelegt_datum'); ?>
		<?php echo $form->error($model, 'angelegt_datum'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'admin'); ?>
		<?php echo $form->textField($model, 'admin'); ?>
		<?php echo $form->error($model, 'admin'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'status'); ?>
		<?php echo $form->dropDownList($model, 'status', Person::$STATUS); ?>
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