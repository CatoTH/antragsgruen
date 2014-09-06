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
 */

/** @var CWebApplication $app */
$app = Yii::app();
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/ckeditor/ckeditor.js');
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/bbcode/plugin.js');

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung->name_kurz) => $this->createUrl("veranstaltung/index"),
	$sprache->get("Antrag")                          => $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)),
	$sprache->get("Neuer Änderungsantrag"),
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");
?>


<h1><?php echo $sprache->get("Änderungsantrag stellen") ?>: <?php echo CHtml::encode($antrag->name); ?></h1>

<?php
/** @var TbActiveForm $form */
$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'id'     => 'horizontalForm',
	'type'   => 'horizontal',
	"action" => $this->createUrl("aenderungsantrag/neu", array("antrag_id" => $antrag->id)),
));

foreach ($hiddens as $name => $value) {
	echo '<input type="hidden" name="' . CHtml::encode($name) . '" value="' . CHtml::encode($value) . '">';
}

Yii::app()->user->setFlash("info", str_replace(array("#1#", "#2#"), array($sprache->get("beantragte"), $sprache->get("Änderungsantrag")), "Bitte wähle nun die Absätze aus, die geändert werden sollen. Du kannst dann die #1# neue Fassung sowie die Begründung für den #2# angeben."));
$this->widget('bootstrap.widgets.TbAlert');

if ($js_protection) {
	?>
	<div class="js_protection_hint">ACHTUNG: Um diese Funktion zu nutzen, muss entweder JavaScript aktiviert sein,
		oder du musst eingeloggt sein.
	</div>
<?php } ?>

<h3><label for="Aenderungsantrag_name_neu">Neuer Titel</label></h3>
<br>
<input id="Aenderungsantrag_name_neu" type="text" value="<?php echo CHtml::encode($aenderungsantrag->name_neu); ?>"
	   name="Aenderungsantrag[name_neu]" style="width: 550px; margin-left: 52px;">
<br>
<br>

<h3><?php echo $sprache->get("Neuer Antragstext"); ?></h3>
<br>
<div
	class="antrags_text_holder ae_absatzwahl_modus aenderungen_moeglich<?php if ($aenderungsantrag->antrag->veranstaltung->getEinstellungen()->zeilenlaenge > 80) echo " kleine_schrift"; ?>"
	style="overflow: auto;">

	<?php

	$absae = $antrag->getParagraphs();
	$text_pre = $aenderungsantrag->getDiffParagraphs();

	foreach ($absae as $i => $abs) {
		/** @var AntragAbsatz $abs */
		echo "<div class='row-fluid row-absatz' id='absatz_" . $i . "'>";

		echo "<div class='absatz_text orig antragabsatz_holder antrags_text_holder_nummern' ";
		if ($text_pre && $text_pre[$i] != "") echo " style='display: none;'";
		echo ">" . $abs->str_html . "</div>";

		echo "<div class='ae_text_holder'>
			<label><input type='checkbox' name='change_text[$i]' data-absatz='$i' class='change_checkbox' ";
		if ($text_pre && $text_pre[$i] != "") echo "checked";
		echo "> Ändern<br></label>
			<h4>Neue Fassung</h4>
			<textarea id='neu_text_$i' name='neu_text[$i]' style='width: 550px; height: 200px;'>";
		$str_neu = ($text_pre && $text_pre[$i] != "" ? $text_pre[$i] : $abs->str_bbcode);
		echo CHtml::encode($str_neu) . "</textarea>";
		echo "<a href='#' class='ae_verwerfen' style='float: right;'>Unverändert lassen</a>";
		echo "<div style='text-align: center;'>";
		$this->widget('bootstrap.widgets.TbButton', array('type' => 'success', 'icon' => 'chevron-right white', 'label' => 'Weiter', 'url' => '#begruendungs_holder'));
		echo "</div></div>\n";

		echo "<div class='antragstext_diff antrags_text_holder_keinenummern absatz_text' ";
		if (!$text_pre || $text_pre[$i] == "") echo "style='display: none;'";
		echo ">";
		if ($text_pre && $text_pre[$i] != "") echo DiffUtils::renderBBCodeDiff2HTML($abs->str_bbcode, $text_pre[$i]);
		else echo $abs->str_html_plain;
		echo "</div>";

		echo "</div>";
	}
	?>
</div>

<div id="begruendungs_holder">
	<h3><label for="ae_begruendung"><?php echo $sprache->get("Begründung für den Änderungsantrag"); ?></label></h3>

	<div class="content">
		<textarea name='ae_begruendung' id="ae_begruendung" style='width: 550px; height: 200px;'><?php
			echo CHtml::encode($aenderungsantrag->aenderung_begruendung);
			?></textarea>
		<input type="hidden" id="ae_begruendung_html" name="ae_begruendung_html"
			   value="<?php echo $aenderungsantrag->antrag->veranstaltung->getEinstellungen()->begruendung_in_html; ?>">

		<?php if ($mode == "bearbeiten") { ?>
			<div class="ae_select_confirm" style="margin-top: 20px;">
				<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'primary', 'icon' => 'ok white', 'label' => 'Speichern')); ?>
			</div>
		<?php } ?>
		<br><br>
	</div>

</div>
<?php

$this->renderPartial($antrag->veranstaltung->getPolicyAenderungsantraege()->getAntragstellerInView(), array(
	"form"             => $form,
	"mode"             => $mode,
	"antrag"           => $antrag,
	"aenderungsantrag" => $aenderungsantrag,
	"antragstellerIn"  => $antragstellerIn,
	"hiddens"          => $hiddens,
	"js_protection"    => $js_protection,
	"sprache"          => $aenderungsantrag->antrag->veranstaltung->getSprache(),
	"veranstaltung"    => $antrag->veranstaltung,
));

$ajax_link = $this->createUrl("aenderungsantrag/ajaxCalcDiff");
?>
<script>
	var antrag_id = <?php echo $antrag->id; ?>,
		nur_ein_absatz = <?php echo ($antrag->veranstaltung->getEinstellungen()->ae_nummerierung_nach_zeile ? "true" : "false"); ?>;

	function antragstext_init_aes() {
		"use strict";
		$(".ae_text_holder input.change_checkbox").not(':checked').parents(".ae_text_holder").hide();
		$(".change_checkbox").parents("label").hide();
		$(".ae_text_holder textarea").each(function () {
			ckeditor_bbcode($(this).attr("id"));
		});

		if ($("#ae_begruendung_html").val() == "1") {
			ckeditor_simplehtml("ae_begruendung");
		} else {
			ckeditor_bbcode("ae_begruendung");
		}

		var aenderungen_moeglich_recals = function () {
			var moeglich = true;
			if (nur_ein_absatz) {
				if ($(".ae_absatzwahl_modus .change_checkbox:checked").length > 0) moeglich = false;
			}
			if (moeglich) {
				$(".ae_absatzwahl_modus").addClass("aenderungen_moeglich");
			} else {
				$(".ae_absatzwahl_modus").removeClass("aenderungen_moeglich");
			}
		};

		$(".ae_absatzwahl_modus .antragabsatz_holder .text").click(function (ev) {
			ev.preventDefault();
			if (!$(".ae_absatzwahl_modus").hasClass("aenderungen_moeglich")) return;
			var $abs = $(this).parents(".row-absatz");
			$abs.find(".change_checkbox").prop("checked", true);
			$abs.find(".ae_text_holder").show().css("display", "block");
			$abs.find(".orig").hide();
			$abs.find(".antragstext_diff").show();
			aenderungen_moeglich_recals();
		});
		$(".ae_absatzwahl_modus .ae_text_holder .ae_verwerfen").click(function (ev) {
			ev.preventDefault();
			if (confirm("Die Änderungen wirklich verwerfen?")) {
				var $abs = $(this).parents(".row-absatz");
				$abs.find(".change_checkbox").prop("checked", false);
				$abs.find(".ae_text_holder").hide();
				$abs.find(".orig").show();
				$abs.find(".antragstext_diff").hide();
				aenderungen_moeglich_recals();
			}
		});

		window.setTimeout(antragstext_show_diff, 3000);
	}

	function antragstext_show_diff() {
		var abss = {},
			str;
		$(".change_checkbox:checked").each(function () {
			var absatznr = $(this).data("absatz");
			str = CKEDITOR.instances["neu_text_" + absatznr].getData();
			abss[absatznr] = str;
		});
		$.ajax({ "url":<?php echo json_encode($ajax_link); ?>, "dataType": "json", "type": "POST", "data": { "antrag_id": antrag_id, "absaetze": abss }, "error": function (xht, status) {
			window.setTimeout(antragstext_show_diff, 10000);
		}, "success": function (dat) {
			for (var i in dat) if (dat.hasOwnProperty(i)) {
				$("#absatz_" + i + " .antragstext_diff").html(dat[i]);
			}
		}});
		window.setTimeout(antragstext_show_diff, 3000);
	}

	$(antragstext_init_aes);
</script>

<?php $this->endWidget(); ?>
