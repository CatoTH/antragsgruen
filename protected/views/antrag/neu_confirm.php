<?php

/**
 * @var AntragController $this
 * @var Antrag $antrag
 * @var Sprache $sprache
 */

/** @var array|Person[] $antragstellerinnen */
$antragstellerinnen = array();
/** @var array|Person[] $unterstuetzerinnen */
$unterstuetzerinnen = array();

if (count($antrag->antragUnterstuetzer) > 0) foreach ($antrag->antragUnterstuetzer as $relatedModel) {
	if ($relatedModel->rolle == IUnterstuetzer::$ROLLE_INITIATOR) $antragstellerinnen[] = $relatedModel->unterstuetzer;
	if ($relatedModel->rolle == IUnterstuetzer::$ROLLE_UNTERSTUETZER) $unterstuetzerinnen[] = $relatedModel->unterstuetzer;
}

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung0->name_kurz) => "/",
	'Neuer Antrag',
	'Bestätigen'
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");
?>

<h1 class="well"><?php echo CHtml::encode($antrag->name); ?></h1>

<div class="antrags_text_holder well well_first">
    <h3>Antragstext</h3>

	<?php

	Yii::app()->user->setFlash("info", "<b>Bitte bestätige</b>, ob der Text richtig übernommen wurde. Wenn ja, klicke unten auf &quot;<b>Antrag einreichen</b>&quot;. Falls nicht, kannst du ihn nocheinmal korrigieren.<br>
<br>
Falls es Probleme gibt, die sich nicht lösen lassen, gib uns bitte per Mail an xxxxx Bescheid. Den Änderungsantrag kannst du auch per E-Mail an die Landesgeschäftsstelle einreichen.");
	$this->widget('bootstrap.widgets.TbAlert');

	?>

    <div class="textholder consolidated">
		<?php
		$absae = $antrag->getParagraphs();
		foreach ($absae as $i=> $abs) {
			/** @var AntragAbsatz $abs */
			echo "<div class='absatz_text orig antragabsatz_holder antrags_text_holder_nummern'>";
			echo $abs->str_html;
			echo "</div>";
		}
		?>
    </div>
</div>

<div class="begruendungs_text_holder well">
    <h3>Begründung</h3>

    <div class="textholder consolidated">
		<?php echo HtmlBBcodeUtils::bbcode2html($antrag->begruendung) ?>
    </div>
</div>

<div class="antrags_text_holder well">
    <h3>UnterstützerInnen</h3>

    <div class="content">
		<?php
		if (count($unterstuetzerinnen) > 0) {
			echo CHtml::openTag('ul');
			foreach ($unterstuetzerinnen as $p) {
				echo CHtml::openTag('li');
				echo CHtml::encode($p->name);
				echo CHtml::closeTag('li');
			}
			echo CHtml::closeTag('ul');

		} else echo "<em>keine</em>";
		?>
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
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'type'=> 'primary', 'icon'=> 'ok white', 'label'=> 'Antrag einreichen')); ?>
    </div>
    <div style="float: left;">
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submitlink', 'url' => "/antrag/aendern/?id=" . $antrag->id, 'icon'=> 'remove', 'label'=> 'Korrigieren')); ?>
    </div>
</div>

<?php $this->endWidget(); ?>