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

while (count($unterstuetzerInnen) < 2) {
    $p                    = new Person();
    $p->name              = "";
    $p->organisation      = "";
    $p->id                = 0;
    $unterstuetzerInnen[] = $p;
}
$bin_organisation = ($antragstellerIn->typ == Person::$TYP_ORGANISATION);

?>
<div class="policy_antragstellerIn_orga_19_fulltext">
    <h3><?= $sprache->get("AntragstellerIn") ?></h3>
    <br>
    <div class="control-group" id="Person_typ_chooser">
        <label class="control-label">Ich bin...</label>

        <div class="controls">
            <label><input type="radio" name="Person[typ]" value="mitglied" required checked> Länderratsdelegierte(r)</label><br>
            <label><input type="radio" name="Person[typ]" value="organisation" required> Gremium</label><br>
        </div>
    </div>

    <?php
    echo $veranstaltung->getPolicyAntraege()->getAntragsstellerInStdForm($veranstaltung, $antragstellerIn);
    ?>

    <div class="control-group" id="Organisation_Beschlussdatum_holder">
        <label class="control-label">Beschlussdatum:</label>

        <div class="controls">
            <input type="text" name="Organisation_Beschlussdatum" placeholder="TT.MM.JJJJ" id="Organisation_Beschlussdatum">
        </div>
    </div>

    <div class="control-group" id="UnterstuetzerInnen">
        <label class="control-label">
            Min. 2 weitere<br>Länderratsdelegierte<br>
            <br>
            <a href="#" class="fulltext_opener"><span class="icon icon-arrow-right"></span> Volltextfeld</a>
            <a href="#" class="fulltext_closer" style="display: none;"><span class="icon icon-arrow-right"></span> Volltextfeld ausblenden</a>
        </label>

        <div class="controle unterstuetzerInnen_list_fulltext_holder" style="display: none; float: left; padding-left: 20px;">
            <textarea name="UnterstuetzerInnen_fulltext" rows="10" cols="60" style="min-width: 300px;"></textarea>
        </div>

        <div class="controls unterstuetzerInnen_list">
            <?php foreach ($unterstuetzerInnen as $u) { ?>
                <input type="hidden" name="UnterstuetzerInnen_id[]" value="<?php echo $u->id; ?>">
                <input type="text" name="UnterstuetzerInnen_name[]" value="<?php echo CHtml::encode($u->name); ?>" placeholder="Name" title="Name der Länderratsdelegierten">
                <input type="text" name="UnterstuetzerInnen_organisation[]" value="<?php echo CHtml::encode($u->organisation); ?>" placeholder="Gremium, LAG..."
                       title="Gremium, LAG...">
                <br>
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
            $andereAntragstellerIn = $("input[name=andere_antragstellerIn]"),
            $beschlussdatum = $("#Organisation_Beschlussdatum_holder");

        $chooser.find("input").change(function () {
            var val = $chooser.find("input:checked").val();
            if (val == "mitglied") {
                $unter.show();
                $unter.find("input[type=text]").prop("required", true);
                $beschlussdatum.hide();
                $beschlussdatum.find("input").removeAttr("required");
                $(".organisation_row").show();
            }
            if (val == "organisation") {
                $unter.hide();
                $unter.find("input[type=text]").prop("required", false);
                $beschlussdatum.show();
                $beschlussdatum.find("input").attr("required", "required");
                $(".organisation_row").hide().find("input").val("");
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
        }).change();
        $unter.find(".unterstuetzerInnen_adder a").click(function (ev) {
            ev.preventDefault();
            $(".unterstuetzerInnen_list").append('<input type="text" name="UnterstuetzerInnen_name[]" value="" placeholder="Name" title="Name der Länderratsdelegierten">\
					<input type="text" name="UnterstuetzerInnen_organisation[]" value="" placeholder="Gremium, LAG..." title="Gremium, LAG...">\
					<br>');
        });
        $unter.find(".fulltext_opener").click(function (ev) {
            ev.preventDefault();
            $unter.find(".unterstuetzerInnen_list").hide();
            $unter.find(".unterstuetzerInnen_list").find("input").removeAttr("required");
            $unter.find(".unterstuetzerInnen_adder").hide();
            $unter.find(".unterstuetzerInnen_list_fulltext_holder").show();
            $unter.find(".fulltext_closer").show();
            $unter.find(".fulltext_opener").hide();
        });
        $unter.find(".fulltext_closer").click(function (ev) {
            ev.preventDefault();
            $unter.find(".unterstuetzerInnen_list").show();
            $unter.find(".unterstuetzerInnen_list").find("input").attr("required", "required");
            $unter.find(".unterstuetzerInnen_adder").show();
            $unter.find(".unterstuetzerInnen_list_fulltext_holder").hide();
            $unter.find(".unterstuetzerInnen_list_fulltext_holder textarea").val("");
            $unter.find(".fulltext_closer").hide();
            $unter.find(".fulltext_opener").show();
        });

        $("#antrag_stellen_form").submit(function (ev) {
            var person_typ = $chooser.find("input:checked").val();
            if (person_typ == "organisation") {
                var datum = $beschlussdatum.find("input").val();
                if (!datum.match(/^[0-9]{2}\. *[0-9]{2}\. *[0-9]{4}$/)) {
                    ev.preventDefault();
                    alert("Bitte gib das Datum der Beschlussfassung im Format TT.MM.JJJJ (z.B. 24.12.2013).");
                    $beschlussdatum.find("input").focus();
                }
            }
        });
    });
</script>
