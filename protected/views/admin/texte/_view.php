<?php
/* @var $this TexteController */
/* @var $data Texte */
?>

<div class="view">

	<?php echo GxHtml::encode($data->getAttributeLabel('id')); ?>:
	<?php echo GxHtml::link(GxHtml::encode($data->id), array('view', 'id' => $data->id)); ?>
    <br>

	<?php echo GxHtml::encode($data->getAttributeLabel('text_id')); ?>:
	<?php echo GxHtml::encode($data->text_id); ?>
    <br>
	<?php if ($data->veranstaltung_id > 0) {
	echo GxHtml::encode($data->getAttributeLabel('veranstaltung_id')); ?>:
	<?php echo GxHtml::encode($data->veranstaltung->name); ?>
    <br>
	<?
}
	echo GxHtml::encode($data->getAttributeLabel('edit_datum')); ?>:
	<?php echo GxHtml::encode($data->edit_datum); ?>
    <br>
	<?php echo GxHtml::encode($data->getAttributeLabel('edit_person')); ?>:
	<?php echo GxHtml::encode($data->editPerson->name); ?>
    <br>

</div>