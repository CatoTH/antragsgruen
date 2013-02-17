<?php
/**
 * @var AntragController $this
 * @var Antrag $antrag
 * @var Sprache $sprache
 */

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung0->name_kurz) => $this->createUrl("site/veranstaltung"),
	"Antrag" => $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)),
	'Neuer Antrag',
	'Bestätigen'
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");
?>

<h1><?php echo $sprache->get("Antrag eingereicht"); ?></h1>
<div class="form well">
	<?php
	echo $text = $antrag->veranstaltung0->getStandardtext("antrag_eingereicht")->getHTMLText();
	?>
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