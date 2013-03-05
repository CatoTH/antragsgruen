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
$ver = $aenderungsantrag->antrag->veranstaltung0;
$this->pageTitle = $sprache->get("Änderungseintrag eingereicht");
?>

<h1><?php echo $sprache->get("Änderungseintrag eingereicht")?></h1>
<div class="form well">
    <?php echo $ver->getStandardtext("ae_eingereicht")->getHTMLText(); ?>
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