<?php

/**
 * @var AenderungsantragController $this
 * @var Aenderungsantrag $aenderungsantrag
 */

$antrag = $aenderungsantrag->antrag;

Yii::app()->getClientScript()->registerScriptFile(Yii::app()->request->baseUrl . '/js/ckeditor/ckeditor.js');
Yii::app()->getClientScript()->registerScriptFile(Yii::app()->request->baseUrl . '/js/antraege_bbcode_plugin.js');

/** @var $antragstellerinnen array|Person[] */
$antragstellerinnen = array();
/** @var $unterstuetzerinnen array|Person[] */
$unterstuetzerinnen = array();

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung0->name_kurz) => "/",
	"Antrag"                                          => "/antrag/anzeige/?id=" . $antrag->id,
	'Änderungsantrag bestätigen'
);?>

<h1 class="well">Änderungsantrag zu <?php echo CHtml::encode($antrag->name); ?></h1>

<div class="antrags_text_holder well well_first">
    <h3>Änderungsantragstext</h3>

	<?php
	Yii::app()->user->setFlash("info", "Bitte kontrolliere kurz, ob der Text richtig übernommen wurde. Wenn ja, kannst du den Änderungsantrag unten bestätigen. Falls nicht, kannst du ihn nocheinmal korrigieren.<br>
<br>
Falls es Probleme gibt, die sich nicht lösen lassen, gib uns bitte per Mail an xxxxx Bescheid. Den Änderungsantrag kannst du auch per E-Mail an die Landesgeschäftsstelle einreichen.");
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
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'type'=> 'primary', 'icon'=> 'ok white', 'label'=> 'Änderungsantrag einreichen')); ?>
    </div>
	<!--
    <div style="float: left;">
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submitlink', 'url' => "/aenderungsantrag/aendern/?id=" . $aenderungsantrag->id, 'icon'=> 'remove', 'label'=> 'Korrigieren')); ?>
    </div>
    -->
</div>

<?php $this->endWidget(); ?>