<?php

/**
 * @var AenderungsantragController $this
 * @var Aenderungsantrag $aenderungsantrag
 * @var Sprache $sprache
 */

$antrag = $aenderungsantrag->antrag;

/** @var $antragstellerinnen array|Person[] */
$antragstellerinnen = array();
/** @var $unterstuetzerinnen array|Person[] */
$unterstuetzerinnen = array();

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung0->name_kurz) => $this->createUrl("site/veranstaltung"),
	$sprache->get("Antrag")                                          => $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)),
	$sprache->get('Änderungsantrag bestätigen'),
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");
?>

<h1 class="well"><?php echo $sprache->get("Änderungsantrag"); ?> zu <?php echo CHtml::encode($antrag->name); ?></h1>

<div class="antrags_text_holder well well_first">
    <h3><?php echo $sprache->get("Neuer Antragstext"); ?></h3>

	<?php
	$mail = "<a href='mailto:" . Yii::app()->params['kontakt_email'] . "'>" . Yii::app()->params['kontakt_email'] . "</a>";
	Yii::app()->user->setFlash("info", str_replace(array("#1#", "#2#"), array($sprache->get("Änderungsantrag"), $mail), $antrag->veranstaltung0->getStandardtext("ae_confirm")->getText()));
	$this->widget('bootstrap.widgets.TbAlert');
	?>

    <div class="textholder consolidated antrags_text_holder_nummern" style="padding-left: 40px; padding-right: 40px;">
		<?php
		echo HtmlBBcodeUtils::bbcode2html($aenderungsantrag->aenderung_text);
		?>
    </div>
</div>

<div class="begruendungs_text_holder well">
    <h3>Begründung</h3>

    <div class="textholder consolidated ">
		<?php echo HtmlBBcodeUtils::bbcode2html($aenderungsantrag->aenderung_begruendung) ?>
    </div>
</div>
<?php
/** @var TbActiveForm $form */
$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'id'  => 'horizontalForm',
	'type'=> 'horizontal',
)); ?>

<input type="hidden" name="<?=AntiXSS::createToken("antragbestaetigen")?>" value="1">

<div class="form-actions">
    <div style="float: right;">
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'type'=> 'primary', 'icon'=> 'ok white', 'label'=> $sprache->get("Änderungsantrag bestätigen"))); ?>
    </div>
	<!--
    <div style="float: left;">
		<?php
		$aendern_link = $this->createUrl("aenderungsantrag/aendern", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id));
		$this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submitlink', 'url' => $aendern_link, 'icon'=> 'remove', 'label'=> 'Korrigieren')); ?>
    </div>
    -->
</div>

<?php $this->endWidget(); ?>