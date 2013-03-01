<?php

/**
 * @var AntragController $this
 * @var $antrag $antrag
 * @var Sprache $sprache
 */

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung0->name_kurz) => $this->createUrl("site/veranstaltung"),
	"Antrag"                                          => $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)),
	"Bearbeiten",
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");

?>

<h1 class="well">Antrag verwalten</h1>

<div class="well well_first">
	<h3>Nachträglich Bearbeiten</h3>

	<a class="btn btn-small btn-info" href="<?=CHtml::encode($this->createUrl("antrag/aendern", array("antrag_id" => $antrag->id)))?>"><i class="icon-wrench icon-white"></i> Ändern</a>

	<br><br>

	<h3>Zurückziehen</h3>

	<?php
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		'id'  => 'horizontalForm',
		'type'=> 'horizontal',
	));
	?>
	<input type="hidden" name="<?=AntiXSS::createToken("antrag_del")?>" value="1">
	<?php

	$this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'type'=> 'danger', 'icon'=> 'trash white', 'label'=> 'Zurückziehen'));

	$this->endWidget();

	?>
	<script>
		$(function () {
			$(".btn-danger").parents("form").submit(function (ev) {
				if (!confirm("Wirklich zurückziehen?")) ev.preventDefault();
			});
		});
	</script>
</div>