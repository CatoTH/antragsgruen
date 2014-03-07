<?php

/**
 * @var AntragController $this
 * @var string $mode
 * @var Antrag $antrag
 * @var array $hiddens
 * @var bool $js_protection
 * @var Sprache $sprache
 * @var Person $antragstellerIn
 */


if ($mode == "neu") {
	?>
	<h3><?= $sprache->get("AntragstellerIn") ?></h3>
	<br>

	<?php echo $form->textFieldRow($antragstellerIn, 'name'); ?>

	<?php echo $form->textFieldRow($antragstellerIn, 'email', array("required" => true)); ?>

	<?php echo $form->textFieldRow($antragstellerIn, 'telefon'); ?>

	<div class="control-group" id="Person_typ_chooser">
		<label class="control-label">Ich bin...</label>

		<div class="controls">
			<label><input type="radio" name="Person[typ]" value="delegiert" required/> einE DelegierteR</label>
			<label><input type="radio" name="Person[typ]" value="mitglied" required/> Parteimitglied (nicht
				delegiert)</label>
			<label><input type="radio" name="Person[typ]" value="organisation" required/> ein Gremium, LAK, ...</label>
		</div>
	</div>

	<div class="control-group" id="UnterstuetzerInnen">
		<label class="control-label">UnterstützerInnen<br>(min. 19)</label>

		<div class="controls">
			<?php for ($i = 0; $i < 19; $i++) { ?>
				<input type="text" name="UnterstuetzerInnen[]" value="" placeholder="Name"
				       title="Name der UnterstützerInnen"><br>
			<?php } ?>
		</div>
	</div>

	<script>
		$(function () {
			var $chooser = $("#Person_typ_chooser");
			var $unter = $("#UnterstuetzerInnen");
			$chooser.find("input").change(function () {
				if ($chooser.find("input:checked").val() == "mitglied") {
					$unter.show();
					$unter.find("input[type=text]").prop("required", true);
				} else {
					$unter.hide();
					$unter.find("input[type=text]").prop("required", false);
				}
			}).change();
		})
	</script>

<?php
}
