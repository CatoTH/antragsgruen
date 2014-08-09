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
	<?php if ($this->veranstaltung->isAdminCurUser()) { ?>
		<label><input type="checkbox" name="andere_antragstellerIn"> Ich lege diesen Antrag für eine andere AntragstellerIn an
			<small>(Admin-Funktion)</small>
		</label>
	<?php } ?>

	<div class="antragstellerIn_daten">
		<?php
		echo $form->textFieldRow($antragstellerIn, 'name');
		echo $form->textFieldRow($antragstellerIn, 'email', array("required" => true));
		echo $form->textFieldRow($antragstellerIn, 'telefon');
		?>
	</div>

	<div class="control-group" id="Person_typ_chooser">
		<label class="control-label">Ich bin...</label>

		<div class="controls">
			<label><input type="radio" name="Person[typ]" value="delegiert" required/> einE DelegierteR</label><br>
			<label><input type="radio" name="Person[typ]" value="mitglied" required/> Parteimitglied (nicht delegiert)</label><br>
			<label><input type="radio" name="Person[typ]" value="organisation" required/> ein Gremium, LAK, ...</label><br>
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
			var $chooser = $("#Person_typ_chooser"),
				$unter = $("#UnterstuetzerInnen"),
				$andereAntragstellerIn = $("input[name=andere_antragstellerIn]");
			$chooser.find("input").change(function () {
				if ($chooser.find("input:checked").val() == "mitglied") {
					$unter.show();
					$unter.find("input[type=text]").prop("required", true);
				} else {
					$unter.hide();
					$unter.find("input[type=text]").prop("required", false);
				}
			}).change();
			if ($andereAntragstellerIn.length > 0) $andereAntragstellerIn.change(function() {
				if ($(this).prop("checked")) {
					$(".antragstellerIn_daten input").each(function() {
						var $input = $(this);
						$input.data("orig", $input.val());
						$input.val("");
					});
				} else {
					$(".antragstellerIn_daten input").each(function() {
						var $input = $(this);
						$input.val($input.data("orig"));
					});
				}
			});
		})
	</script>

<?php
}
