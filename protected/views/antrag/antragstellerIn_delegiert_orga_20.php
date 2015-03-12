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
 * @var Person[] $unterstuetzerInnen
 */

while (count($unterstuetzerInnen) < 19) {
	$p                    = new Person();
	$p->name              = "";
	$p->organisation      = "";
	$p->id                = 0;
	$unterstuetzerInnen[] = $p;
}

$bin_organisation = ($antragstellerIn->typ == Person::$TYP_ORGANISATION);
$bin_delegiert = ($antragstellerIn->typ == Person::$TYP_PERSON && count($unterstuetzerInnen) == 0);
$bin_mitglied = ($antragstellerIn->typ == Person::$TYP_PERSON && count($unterstuetzerInnen) > 0);

?>
<div class="antragstellerIn_delegiert_orga_20">
	<h3><?= $sprache->get("AntragstellerIn") ?></h3>
	<br>
	<?php
	echo $veranstaltung->getPolicyAntraege()->getAntragsstellerInStdForm($veranstaltung, $antragstellerIn);
	?>

	<div class="control-group" id="Person_typ_chooser">
		<label class="control-label">Ich bin...</label>

		<div class="controls">
			<label><input type="radio" name="Person[typ]" value="delegiert" required <?php if ($bin_delegiert) echo "checked"; ?>> einE DelegierteR</label><br>
			<label><input type="radio" name="Person[typ]" value="mitglied" required <?php if ($bin_mitglied) echo "checked"; ?>> Parteimitglied (nicht delegiert)</label><br>
			<label><input type="radio" name="Person[typ]" value="organisation" required <?php if ($bin_organisation) echo "checked"; ?>> ein Gremium, LAK, ...</label><br>
		</div>
	</div>

	<div class="control-group" id="UnterstuetzerInnen">
		<label class="control-label">UnterstützerInnen<br>(min. 19)</label>

		<div class="controls">
			<?php foreach ($unterstuetzerInnen as $u) { ?>
				<input type="hidden" name="UnterstuetzerInnen_id[]" value="<?php echo $u->id; ?>">
				<input type="text" name="UnterstuetzerInnen_name[]" value="<?php echo CHtml::encode($u->name); ?>" placeholder="Name" title="Name der UnterstützerInnen"><br>
			<?php } ?>
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
	})
</script>
