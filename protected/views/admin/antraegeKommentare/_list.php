<?php
/* @var $this AntraegeKommentareController */
/* @var $data AntragKommentar */
?>

<div class="view">

	<?php echo GxHtml::encode($data->getAttributeLabel('id')); ?>:
	<?php echo GxHtml::link(GxHtml::encode($data->id), array('update', 'id' => $data->id)); ?>
    <br>

    Von:
	<?php echo GxHtml::encode($data->verfasserIn->name); ?>
    <br>
	<?php echo GxHtml::encode($data->getAttributeLabel('antrag_id')); ?>:
	<?php echo GxHtml::encode(GxHtml::valueEx($data->antrag)); ?>
    <br>
	<?php echo GxHtml::encode($data->getAttributeLabel('absatz')); ?>:
	<?php echo GxHtml::encode($data->absatz); ?>
    <br>
	<?php echo GxHtml::encode($data->getAttributeLabel('datum')); ?>:
	<?php echo HtmlBBcodeUtils::formatMysqlDateTime($data->datum); ?>
    <br>
	<?php echo GxHtml::encode($data->getAttributeLabel('status')); ?>:
	<?php if ($data->status != "") echo GxHtml::encode(AntragKommentar::$STATI[$data->status]); ?>
    <br>

</div>
<hr>