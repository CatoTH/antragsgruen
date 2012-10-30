<?php
/**
 * @var AenderungsantragController $this
 * @var Antrag $antrag
 * @var Sprache $sprache
 */

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung0->name_kurz) => "/",
	'Neuer Antrag',
	'Bestätigen'
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");
?>

<h1>Antrag eingereicht</h1>
<div class="form well">
    <p>Der Antrag wurde eingereicht. Eventuell prüfen wir die Anträge vorab noch, um zu verhindern, dass Spam-Bots hier Inhalte einstellen. Bei Problemen melde dich einfach per Mail
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
			'url'       => "/",
			'label'     => 'Zurück zur Startseite',
		));
		$this->endWidget();

		?></p>
</div>