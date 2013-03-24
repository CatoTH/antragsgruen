<?php
/**
 * @var $form GxActiveForm
 * @var $this TexteController
 * @var $model Texte
 */

/** @var CWebApplication $app */
$app = Yii::app();
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/ckeditor/ckeditor.js');
//$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/ckeditor.abbr/plugin.js');
?>

<div class="form">
	<?php

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

			function ckeditor_full(id) {

				CKEDITOR.replace(id, {
					allowedContent: true,
					// Remove unused plugins.
					//removePlugins: 'bidi,dialogadvtab,div,filebrowser,flash,format,forms,horizontalrule,iframe,justify,liststyle,pagebreak,showborders,stylescombo,table,tabletools,templates',
					//removePlugins: 'stylescombo,format,save,newpage,print,templates,showblocks,specialchar,about,preview,pastetext,pastefromword,magicline' + ',sourcearea',
					extraPlugins: 'autogrow,mediaembed',
					scayt_sLang: 'de_DE',
					toolbar:
						[
							{ name: 'document',    items : [ 'Source' ] },
							{ name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
							{ name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock' ] },
							{ name: 'links',       items : [ 'Link','Unlink' ] },
							{ name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
							{ name: 'editing',     items : [ 'Find','Replace','-','SelectAll','-','SpellChecker', 'Scayt' ] },
							// { name: 'forms',       items : [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField' ] },
							{ name: 'insert',      items : [ 'Image','MediaEmbed','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak' ] },
							{ name: 'styles',      items : [ 'Styles','Format','Font','FontSize' ] },
							{ name: 'colors',      items : [ 'TextColor','BGColor' ] },
							{ name: 'tools',       items : [ 'Maximize', 'ShowBlocks','-','About' ] }
						]

				});

			}
			$(function () {
				ckeditor_full("Texte_text");
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
</div>