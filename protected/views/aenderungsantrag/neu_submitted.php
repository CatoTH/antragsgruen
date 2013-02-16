<?php
/**
 * @var AenderungsantragController $this
 * @var Aenderungsantrag $aenderungsantrag
 * @var Sprache $sprache
 */

$this->breadcrumbs = array(
	CHtml::encode($aenderungsantrag->antrag->veranstaltung0->name_kurz) => $this->createUrl("site/veranstaltung"),
	$sprache->get("Antrag"),
	$sprache->get("Neuer Änderungsantrag")
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");
?>

<h1>Antrag eingereicht</h1>
<div class="form well">
    <p>Der <?php echo $sprache->get("Änderungsantrag"); ?> wurde eingereicht. Eventuell prüfen wir die <?php echo $sprache->get("Änderungsantrag"); ?> vorab noch, um zu verhindern, dass Spam-Bots hier Inhalte einstellen. Bei Problemen melde dich einfach per Mail
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