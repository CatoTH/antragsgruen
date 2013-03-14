<?php
Yii::app()->getClientScript()->registerScriptFile(Yii::app()->request->baseUrl . '/js/ckeditor/ckeditor.js');
Yii::app()->getClientScript()->registerScriptFile(Yii::app()->request->baseUrl . '/js/ckeditor.abbr/plugin.js');
?>

<div class="form">


	<?php
	/* @var $this TexteController */
	/* @var $form GxActiveForm */
	/* @var $model Texte */

	$form = $this->beginWidget('GxActiveForm', array(
		'id'                   => 'texte-form',
		'enableAjaxValidation' => false,
	));

	?>

    <p class="note">
		<?php echo Yii::t('app', 'Fields with'); ?> <span class="required">*</span> <?php echo Yii::t('app', 'are required'); ?>.
    </p>

	<?php echo $form->errorSummary($model); ?>

    <div>
		<?php echo $form->labelEx($model, 'text_id'); ?>
		<?php echo $form->textField($model, 'text_id', array('maxlength' => 20)); ?>
		<?php echo $form->error($model, 'text_id'); ?>
    </div>
    <!-- row -->
    <div style="margin-left: 10px;">
		<?php echo $form->textArea($model, 'text'); ?>
		<?php echo $form->error($model, 'text'); ?>
    </div>

	<?php

	if ($model->editPerson != null) echo "Zuletzt geändert: " . $model->edit_datum . " von " . CHtml::encode($model->editPerson->name);

	if (in_array($model->text_id, Veranstaltung::getHTMLStandardtextIDs())) {
	?>
    <script>
        $(function () {
            CKEDITOR.replace('Texte_text', {'customConfig':"/js/ckconfig-html.js", extraPlugins : 'abbr', width:690 });
        })
    </script>
	<?php } ?>

    <br>

    <div class="saveholder">
		<?php
		echo GxHtml::submitButton(Yii::t('app', 'Save'), array("class" => "btn btn-primary"));
		$this->endWidget();
		?>
    </div>
</div><!-- form -->