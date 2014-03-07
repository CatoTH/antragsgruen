<?php

/**
 * @var AntragController $this
 * @var Antrag $model
 * @var Person $antragstellerIn
 * @var Veranstaltung $veranstaltung
 * @var array $model_unterstuetzerInnen
 * @var array $hiddens
 * @var bool $js_protection
 * @var bool $login_warnung
 * @var Sprache $sprache
 */

/** @var CWebApplication $app */
$app = Yii::app();
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/ckeditor/ckeditor.js');
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/bbcode/plugin.js');
//$app->getClientScript()->registerScriptFile(Yii::app()->request->baseUrl . '/bbcode/plugin.js');

$this->breadcrumbs = array(
	CHtml::encode($model->veranstaltung->name_kurz) => $this->createUrl("veranstaltung/index", array("veranstaltung_id" => $model->veranstaltung->url_verzeichnis)),
	'Neuer Antrag',
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");

?>
	<h1><?php echo $sprache->get("Antrag stellen") ?></h1>

	<div class="form content">
		<fieldset>
			<legend><?php echo $sprache->get("Voraussetzungen für einen Antrag") ?></legend>
		</fieldset>

		<?php
		echo $veranstaltung->getPolicyAntraege()->getOnCreateDescription();


		/** @var TbActiveForm $form */
		$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
			'id'   => 'horizontalForm',
			'type' => 'horizontal',
		));

		foreach ($hiddens as $name => $value) {
			echo '<input type="hidden" name="' . CHtml::encode($name) . '" value="' . CHtml::encode($value) . '">';
		}

		if ($login_warnung) {
			Yii::app()->user->setFlash('error', '<strong>Achtung!</strong> Es ist zwar auch möglich, Anträge einzureichen, ohne eingeloggt zu sein. Allerdings kann man nur eingeloggt den Antrag später wieder bearbeiten, daher empfehlen wir sehr, <a href="' . CHtml::encode($this->createUrl("veranstaltung/login")) . '" style="font-weight: bold;">dich einzuloggen</a>.');
			$this->widget('bootstrap.widgets.TbAlert', array(
				'block' => true,
				'fade'  => true,
			));
		}

		if ($js_protection) {
			?>
			<div class="js_protection_hint">ACHTUNG: Um diese Funktion zu nutzen, muss entweder JavaScript aktiviert sein, oder du musst eingeloggt sein.</div>
		<?php } ?>

		<fieldset>

			<label class="legend" for="Antrag_name">Antragstitel</label>
			<input name="Antrag[name]" id="Antrag_name" type="text">

		</fieldset>

		<fieldset>
			<label class="legend">Antragstyp</label>
			<?php
			foreach (Antrag::$TYPEN as $id => $name) {
				echo '<label class="radio"><input name="Antrag[typ]" value="' . $id . '" type="radio" ';
				if ($model->typ == $id) echo ' checked';
				echo '> ' . CHtml::encode($name) . '</label>';
			}
			?>
		</fieldset>


		<fieldset>

			<legend>Antragstext</legend>

			<div class="control-group text_full_width">
				<label style="display: none;" class="control-label required" for="Antrag_text">
					Antragstext
					<span class="required">*</span>
				</label>

				<div class="controls">
					<!--<a href="#" onClick="alert('TODO'); return false;">&gt; Text aus einem Pad kopieren</a><br>-->
					<textarea id="Antrag_text" class="span8" name="Antrag[text]" rows="5" cols="80"><?= CHtml::encode($model->text) ?></textarea>
				</div>

			</div>


			<legend>Begründung</legend>

			<div class="control-group text_full_width">
				<label style="display: none;" class="control-label required" for="Antrag_begruendung">
					Begründung
					<span class="required">*</span>
				</label>

				<div class="controls">
					<textarea id="Antrag_begruendung" class="span8" name="Antrag[begruendung]" rows="5" cols="80"><?= CHtml::encode($model->begruendung) ?></textarea>
				</div>

			</div>
		</fieldset>
	</div>
<?php
$this->renderPartial($model->veranstaltung->getPolicyAntraege()->getAntragstellerInView(), array(
	"form"                      => $form,
	"mode"                      => $mode,
	"antrag"                    => $model,
	"antragstellerIn"           => $antragstellerIn,
	"antrag_unterstuetzerInnen" => $model_unterstuetzerInnen,
	"veranstaltung"             => $veranstaltung,
	"hiddens"                   => $hiddens,
	"js_protection"             => $js_protection,
	"login_warnung"             => Yii::app()->user->isGuest,
	"sprache"                   => $model->veranstaltung->getSprache(),
));
?>
	<div class="form-actions content">
		<div style="float: left;">
			<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'reset', 'icon' => 'remove', 'label' => 'Reset')); ?>
		</div>
		<div style="float: right;">
			<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'primary', 'icon' => 'ok white', 'label' => 'Weiter')); ?>
		</div>
	</div>


	<script>
		$(function () {
			ckeditor_bbcode("Antrag_text");
			ckeditor_bbcode("Antrag_begruendung");
		})
	</script>

<?php $this->endWidget(); ?>