<?php
/**
 * @var AenderungsantragController $this
 * @var Aenderungsantrag $aenderungsantrag
 * @var Sprache $sprache
 */

$this->breadcrumbs = array(
	CHtml::encode($aenderungsantrag->antrag->veranstaltung->name_kurz) => $this->createUrl("veranstaltung/index"),
	$sprache->get("Antrag"),
	$sprache->get("Neuer Änderungsantrag")
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");
$ver = $aenderungsantrag->antrag->veranstaltung;
$this->pageTitle = $sprache->get("Änderungseintrag eingereicht");
?>

<h1><?php echo $sprache->get("Änderungseintrag eingereicht") ?></h1>
<?php echo $ver->getStandardtext("ae_eingereicht")->getHTMLText(); ?>
<p><?php

	/** @var TbActiveForm $form */
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		'id'   => 'horizontalForm',
		'type' => 'horizontal',
	));

	$this->widget('bootstrap.widgets.TbButton', array(
		'type'       => 'primary',
		'size'       => 'large',
		'buttonType' => 'submitlink',
		'url'        => $this->createUrl("veranstaltung/index"),
		'label'      => 'Zurück zur Startseite',
	));
	$this->endWidget();

	?></p>
