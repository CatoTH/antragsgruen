<?php

/**
 * @var AenderungsantragController $this
 * @var Aenderungsantrag $aenderungsantrag
 * @var Sprache $sprache
 */

$antrag = $aenderungsantrag->antrag;

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung0->name_kurz) => "/",
	"Antrag"                                          => "/antrag/anzeige/?id=" . $antrag->id,
	"Änderungsantrag"                                 => "/aenderungsantrag/anzeige/?id=" . $aenderungsantrag->id,
	"Bearbeiten",
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");

?>

<h1 class="well">Änderungsantrag verwalten</h1>

<div class="well well_first">
	<h3>Nachträglich Bearbeiten</h3>

	<a class="btn btn-small btn-info" href="/aenderungsantrag/aendern/?id=<?php echo $aenderungsantrag->id; ?>"><i class="icon-wrench icon-white"></i> Ändern</a>

	<br><br>

	<h3>Zurückziehen</h3>

	<?php
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		'id'  => 'horizontalForm',
		'type'=> 'horizontal',
	));
	?>
	<input type="hidden" name="<?=AntiXSS::createToken("ae_del")?>" value="1">
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