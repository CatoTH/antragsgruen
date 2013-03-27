<?php
/* @var $this TexteController */
/* @var $data Texte */
?>

<table class="view">

	<tr>
	<th><?php echo GxHtml::encode($data->getAttributeLabel('text_id')); ?>:</th>
	<td><?php echo GxHtml::link(GxHtml::encode($data->text_id) . " (ID " . GxHtml::encode($data->id) . ")", array('update', 'id' => $data->id)); ?></td>
	</tr>

	<tr>
		<th>Veranstaltung:</th>
		<td>
	<?php if ($data->veranstaltung_id > 0) echo GxHtml::encode($data->veranstaltung->name); ?>
		</td>
	</tr>
	<tr>
	<th><?php echo GxHtml::encode($data->getAttributeLabel('edit_datum')); ?>:</th>
	<td><?php echo GxHtml::encode($data->edit_datum); ?></td>
	</tr>

</table>