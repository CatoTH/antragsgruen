<?php

/**
 * @var AntragController $this
 * @var Antrag $model
 * @var Person $antragstellerin
 * @var Veranstaltung $veranstaltung
 * @var array $model_unterstuetzer
 * @var array $hiddens
 * @var bool $js_protection
 * @var bool $login_warnung
 * @var Sprache $sprache
 */

Yii::app()->getClientScript()->registerScriptFile(Yii::app()->request->baseUrl . '/js/ckeditor/ckeditor.js');
Yii::app()->getClientScript()->registerScriptFile(Yii::app()->request->baseUrl . '/js/ckeditor.bbcode.js');

$this->breadcrumbs = array(
	CHtml::encode($model->veranstaltung0->name_kurz) => $this->createUrl("site/veranstaltung"),
	'Neuer Antrag',
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");

?>
<h1 class="well"><?php echo $sprache->get("Antrag stellen")?></h1>

<div class="form well well_first">
    <fieldset>
        <legend><?php echo $sprache->get("Voraussetzungen für einen Antrag")?></legend>
    </fieldset>

	<?php
	echo $veranstaltung->getPolicyAntraege()->getOnCreateDescription();
	?>
</div>


<div class="form well">
	<?php

	/** @var TbActiveForm $form */
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		'id'  => 'horizontalForm',
		'type'=> 'horizontal',
	));

	foreach ($hiddens as $name=> $value) {
		echo '<input type="hidden" name="' . CHtml::encode($name) . '" value="' . CHtml::encode($value) . '">';
	}

	if ($login_warnung) {
		Yii::app()->user->setFlash('error', '<strong>Achtung!</strong> Es ist zwar auch möglich, Anträge einzureichen, ohne eingeloggt zu sein. Allerdings kann man nur eingeloggt den Antrag später wieder bearbeiten, daher empfehlen wir sehr, <a href="' . CHtml::encode($this->createUrl("site/login")) . '" style="font-weight: bold;">dich einzuloggen</a>.');
		$this->widget('bootstrap.widgets.TbAlert', array(
			'block'=> true,
			'fade' => true,
		));
	}

	if ($js_protection) {
		?>
        <div class="js_protection_hint">ACHTUNG: Um diese Funktion zu nutzen, muss entweder JavaScript aktiviert sein, oder du musst eingeloggt sein.</div>
		<?php } ?>

    <fieldset>

        <legend>Antragstyp</legend>

		<?php
		echo $form->textFieldRow($model, 'name', array('labelOptions'=> array('label'=> 'Antragstitel')));

		echo $form->radioButtonListRow($model, 'typ', Antrag::$TYPEN);

		$stati = array(
			Antrag::$STATUS_ENTWURF => "Entwurf",
		);
		if ($model->status == Antrag::$STATUS_UNBESTAETIGT) $stati[Antrag::$STATUS_UNBESTAETIGT] = "Fertiger Antrag";
		if ($model->status == Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT) $stati[Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT] = "Fertiger Antrag";

		echo $form->radioButtonListRow($model, 'status', $stati);
		?>
    </fieldset>

</div>


<div class="form well">
    <fieldset>

        <legend>Antragstext</legend>

        <div class="control-group ">
            <label style="display: none;" class="control-label required" for="Antrag_text">
                Antragstext
                <span class="required">*</span>
            </label>

            <div class="controls">
                <!--<a href="#" onClick="alert('TODO'); return false;">&gt; Text aus einem Pad kopieren</a><br>-->
                <textarea id="Antrag_text" class="span8" name="Antrag[text]" rows="5" cols="80"><?=CHtml::encode($model->text)?></textarea>
            </div>

        </div>


        <legend>Begründung</legend>

        <div class="control-group ">
            <label style="display: none;" class="control-label required" for="Antrag_begruendung">
                Begründung
                <span class="required">*</span>
            </label>

            <div class="controls">
                <textarea id="Antrag_begruendung" class="span8" name="Antrag[begruendung]" rows="5" cols="80"><?=CHtml::encode($model->begruendung)?></textarea>
            </div>

        </div>
    </fieldset>
</div>
<?php
$this->renderPartial($model->veranstaltung0->getPolicyAntraege()->getAntragsstellerInView(), array(
	"form"                => $form,
	"model"               => $model,
	"antragstellerin"     => $antragstellerin,
	"model_unterstuetzer" => $model_unterstuetzer,
	"veranstaltung"       => $veranstaltung,
	"hiddens"             => $hiddens,
	"js_protection"       => $js_protection,
	"login_warnung"       => Yii::app()->user->isGuest,
	"sprache"             => $model->veranstaltung0->getSprache(),
));
?>
<div class="form-actions">
    <div style="float: left;">
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'reset', 'icon'=> 'remove', 'label'=> 'Reset')); ?>
    </div>
    <div style="float: right;">
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'type'=> 'primary', 'icon'=> 'ok white', 'label'=> 'Weiter')); ?>
    </div>
</div>


<script>
    $(function () {
        CKEDITOR.replace('Antrag_text', {'toolbar':'Animexx', 'customConfig':"/js/ckconfig.js", width:660 });
        CKEDITOR.replace('Antrag_begruendung', {'toolbar':'Animexx', 'customConfig':"/js/ckconfig.js", width:660 });
    })
</script>

<?php $this->endWidget(); ?>