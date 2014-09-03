<?php

/**
 * @var AntragController $this
 * @var string $mode
 * @var Antrag $antrag
 * @var array $hiddens
 * @var bool $js_protection
 * @var Sprache $sprache
 * @var Person $antragstellerIn
 * @var Veranstaltung $veranstaltung
 */


if ($mode == "neu") {
	?>
	<div class="policy_antragstellerIn_orga_5">
		<h3><?= $sprache->get("AntragstellerIn") ?></h3>
		<br>
		<?php
		echo $veranstaltung->getPolicyAntraege()->getAntragsstellerInStdForm($veranstaltung, $antragstellerIn);
		?>

		<div class="control-group" id="Person_typ_chooser">
			<label class="control-label">Ich bin...</label>

			<div class="controls">
				<label><input type="radio" name="Person[typ]" value="mitglied" required checked> Parteimitglied</label><br>
				<label><input type="radio" name="Person[typ]" value="organisation" required> Gremium, LAG...</label><br>
			</div>
		</div>

		<div class="control-group" id="UnterstuetzerInnen">
			<label class="control-label">UnterstützerInnen2<br>(min. 4)</label>

			<div class="controls unterstuetzerInnen_list">
				<?php for ($i = 0; $i < 4; $i++) { ?>
					<div>
						<input type="text" name="UnterstuetzerInnen_name[]" value="" placeholder="Name" title="Name der UnterstützerInnen">
						<input type="text" name="UnterstuetzerInnen_organisation[]" value="" placeholder="Gremium, LAG..." title="Gremium, LAG...">
					</div>
				<?php } ?>
			</div>
			<div class="unterstuetzerInnen_adder">
				<a href="#"><span class="icon icon-plus"></span> UnterstützerIn hinzufügen</a>
			</div>
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
			if ($andereAntragstellerIn.length > 0) $andereAntragstellerIn.change(function () {
				if ($(this).prop("checked")) {
					$(".antragstellerIn_daten input").each(function () {
						var $input = $(this);
						$input.data("orig", $input.val());
						$input.val("");
					});
				} else {
					$(".antragstellerIn_daten input").each(function () {
						var $input = $(this);
						$input.val($input.data("orig"));
					});
				}
			});
			$(".unterstuetzerInnen_adder a").click(function (ev) {
				ev.preventDefault();
				$(".unterstuetzerInnen_list").append('<div><input type="text" name="UnterstuetzerInnen_name[]" value="" placeholder="Name" title="Name der UnterstützerInnen">\
					<input type="text" name="UnterstuetzerInnen_organisation[]" value="" placeholder="Gremium, LAG..." title="Gremium, LAG...">\
					</div>');
			})
		})
	</script>

<?php
}
