<?php

/**
 * @var AenderungsantragController $this
 * @var string $mode
 * @var Antrag $antrag
 * @var Aenderungsantrag $aenderungsantrag
 * @var array $hiddens
 * @var bool $js_protection
 * @var Sprache $sprache
 * @var Person $antragstellerIn
 * @var TbActiveForm $form
 */

if ($mode == "neu") {
	?>
	<h3><?= $sprache->get("AntragstellerIn") ?></h3>
    <br>
    <?php if ($this->veranstaltung->isAdminCurUser()) { ?>
        <label><input type="checkbox" name="andere_antragstellerIn"> Ich lege diesen Antrag für eine andere AntragstellerIn an
            <small>(Admin-Funktion)</small>
        </label>
    <?php } ?>

	<div class="control-group "><label class="control-label" for="Person_name">Name(n)</label>
		<div class="controls">
			<input name="Person[name]" id="Person_name" type="text" maxlength="100" value="<?php echo CHtml::encode($antragstellerIn->name); ?>">
			<?php if (!Yii::app()->user->isGuest) { ?><br>
				<small><strong>Hinweis:</strong> Wird der Name hier geändert, ändert er sich auch bei allen anderen Anträgen, die mit diesem Zugang eingereicht wurden.</small>
			<?php } ?>
		</div>
	</div>

	<?php echo $form->textFieldRow($antragstellerIn, 'email'); ?>

	<?php echo $form->textFieldRow($antragstellerIn, 'telefon'); ?>


	<div class="control-group" id="UnterstuetzerInnen" style="display: none;">
		<label class="control-label">UnterstützerInnen</label>

		<div class="controls"></div>
	</div>

	<div style="padding-left: 162px; margin-top: -15px; margin-bottom: 20px;">
		<a href="#" onClick="return add_unterstuetzerInnen();"><span class="icon-down-open"></span> Weitere
			UnterstützerInnen angeben</a>
	</div>

	<script>
		function add_unterstuetzerInnen() {
			var $u = $("#UnterstuetzerInnen"), str = "";
			$u.show();
			for (var i = 0; i < 5; i++) str += '<input type="text" name="UnterstuetzerInnen[]" value="" placeholder="Name" title="Name der UnterstützerInnen"><br>';
			$u.find(".controls").append(str);
			return false;
		}
	</script>



	<div class="ae_select_confirm">
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'primary', 'icon' => 'ok white', 'label' => $sprache->get("Änderungsantrag stellen"))); ?>
	</div>

	<br><br>
<?php
}
