<?php
/* @var $this AenderungsantraegeKommentareController */
/* @var $data AenderungsantragKommentar */
?>

<div class="view">

	<?php echo GxHtml::encode($data->getAttributeLabel('id')); ?>:
	<?php echo GxHtml::link(GxHtml::encode($data->id), array('update', 'id' => $data->id)); ?>
	<br>

	<?php echo GxHtml::encode($data->getAttributeLabel('person_id')); ?>:
		<?php echo GxHtml::encode(CHtml::encode($data->verfasserIn->name)); ?>
	<br>
	<?php echo GxHtml::encode($data->getAttributeLabel('aenderungsantrag_id')); ?>:
		<?php echo GxHtml::encode($data->aenderungsantrag->revision_name . " zu " . $data->aenderungsantrag->antrag->name); ?>
	<br>
	<?php echo GxHtml::encode($data->getAttributeLabel('datum')); ?>:
	<?php echo HtmlBBcodeUtils::formatMysqlDateTime($data->datum); ?>
	<br>
	<?php echo GxHtml::encode($data->getAttributeLabel('status')); ?>:
	<?php if ($data->status != "") echo GxHtml::encode(AenderungsantragKommentar::$STATI[$data->status]); ?>
	<br>

</div>