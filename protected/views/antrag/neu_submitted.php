<?php
/**
 * @var AntragController $this
 * @var Antrag $antrag
 * @var Sprache $sprache
 */

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung0->name_kurz) => $this->createUrl("site/veranstaltung"),
	"Antrag" => $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)),
	'Neuer Änderungsantrag',
	'Bestätigen'
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");
?>

<h1>Änderungsantrag eingereicht</h1>
<div class="form well">
<p>Der Änderungsantrag wurde eingereicht. Eventuell prüfen wir die Anträge vorab noch, um zu verhindern, dass Spam-Bots hier Inhalte einstellen. Bei Problemen melde dich einfach per Mail
	bei der LGS.</p>
<p><?php

	/** @var TbActiveForm $form */
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		'id'  => 'horizontalForm',
		'type'=> 'horizontal',
	));

	$this->widget('bootstrap.widgets.TbButton', array(
		'type'      => 'primary',
		'size'      => 'large',
		'buttonType'=> 'submitlink',
		'url'       => $this->createUrl("site/veranstaltung"),
		'label'     => 'Zurück zur Startseite',
	));
	$this->endWidget();

	?></p>
</div>