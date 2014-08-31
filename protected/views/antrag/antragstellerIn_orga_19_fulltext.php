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
    <div class="policy_antragstellerIn_orga_5">
        <h3><?= $sprache->get("AntragstellerIn") ?></h3>
        <br>
        <?php if ($this->veranstaltung->isAdminCurUser()) { ?>
            <label><input type="checkbox" name="andere_antragstellerIn"> Ich lege diesen Antrag für eine andere AntragstellerIn an
                <small>(Admin-Funktion)</small>
            </label>
        <?php } ?>

        <div class="antragstellerIn_daten">
            <div class="control-group "><label class="control-label" for="Person_name">Name(n)</label>

                <div class="controls"><input name="Person[name]" id="Person_name" type="text" maxlength="100" value="<?php
                    echo CHtml::encode($antragstellerIn->name);
                    ?>"></div>
            </div>

            <div class="control-group "><label class="control-label" for="Person_organisation">Gremium, LAG...</label>

                <div class="controls"><input name="Person[organisation]" id="Person_organisation" type="text" maxlength="100" value="<?php
                    echo CHtml::encode($antragstellerIn->organisation);
                    ?>"></div>
            </div>

            <div class="control-group "><label class="control-label" for="Person_email">E-Mail</label>

                <div class="controls"><input required="required" name="Person[email]" id="Person_email" type="text" maxlength="200" value="<?php
                    echo CHtml::encode($antragstellerIn->email);
                    ?>"></div>
            </div>

            <div class="control-group "><label class="control-label" for="Person_telefon">Telefon</label>

                <div class="controls"><input required="required" name="Person[telefon]" id="Person_telefon" type="text" maxlength="100" value="<?php
                    echo CHtml::encode($antragstellerIn->telefon);
                    ?>"></div>
            </div>
        </div>

        <div class="control-group" id="Person_typ_chooser">
            <label class="control-label">Ich bin...</label>

            <div class="controls">
                <label><input type="radio" name="Person[typ]" value="mitglied" required checked> Delegiert</label><br>
                <label><input type="radio" name="Person[typ]" value="organisation" required> Gremium, LAG...</label><br>
            </div>
        </div>

        <div class="control-group" id="UnterstuetzerInnen">
            <label class="control-label">
                UnterstützerInnen<br>
                (min. 19)<br>
                <br>
                <a href="#" class="fulltext_opener"><span class="icon icon-arrow-right"></span> Volltextfeld</a>
                <a href="#" class="fulltext_closer" style="display: none;"><span class="icon icon-arrow-right"></span> Volltextfeld ausblenden</a>
            </label>

            <div class="controle unterstuetzerInnen_list_fulltext_holder" style="display: none; float: left; padding-left: 20px;">
                <textarea name="UnterstuetzerInnen_fulltext" rows="10" cols="60" style="min-width: 300px;"></textarea>
            </div>

            <div class="controls unterstuetzerInnen_list">
                <?php for ($i = 0; $i < 19; $i++) { ?>
                    <input type="text" name="UnterstuetzerInnen_name[]" value="" placeholder="Name" title="Name der UnterstützerInnen">
                    <input type="text" name="UnterstuetzerInnen_organisation[]" value="" placeholder="Gremium, LAG..." title="Gremium, LAG...">
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
            $unter.find(".unterstuetzerInnen_adder a").click(function(ev) {
                ev.preventDefault();
                $(".unterstuetzerInnen_list").append('<input type="text" name="UnterstuetzerInnen_name[]" value="" placeholder="Name" title="Name der UnterstützerInnen">\
					<input type="text" name="UnterstuetzerInnen_organisation[]" value="" placeholder="Gremium, LAG..." title="Gremium, LAG...">\
					<br>');
            });
            $unter.find(".fulltext_opener").click(function(ev) {
                ev.preventDefault();
                $unter.find(".unterstuetzerInnen_list").hide();
                $unter.find(".unterstuetzerInnen_list").find("input").removeAttr("required");
                $unter.find(".unterstuetzerInnen_adder").hide();
                $unter.find(".unterstuetzerInnen_list_fulltext_holder").show();
                $unter.find(".fulltext_closer").show();
                $unter.find(".fulltext_opener").hide();
            });
            $unter.find(".fulltext_closer").click(function(ev) {
                ev.preventDefault();
                $unter.find(".unterstuetzerInnen_list").show();
                $unter.find(".unterstuetzerInnen_list").find("input").attr("required", "required");
                $unter.find(".unterstuetzerInnen_adder").show();
                $unter.find(".unterstuetzerInnen_list_fulltext_holder").hide();
                $unter.find(".unterstuetzerInnen_list_fulltext_holder textarea").val("");
                $unter.find(".fulltext_closer").hide();
                $unter.find(".fulltext_opener").show();
            });
        })
    </script>

<?php
}
