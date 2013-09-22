<?php

/**
 * @var AntragController $this
 * @var Antrag $antrag
 * @var array|Aenderungsantrag[] $aenderungsantraege
 * @var Sprache $sprache
 */

/** @var CWebApplication $app */
$app = Yii::app();
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/ckeditor/ckeditor.js');
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/bbcode/plugin.js');

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung->name_kurz) => $this->createUrl("veranstaltung/index"),
	$sprache->get("Antrag")                          => $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)),
	$sprache->get("ÄAs einpflegen")
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");

$this->pageTitle = $antrag->nameMitRev() . " (" . $antrag->veranstaltung->name . ", Antragsgrün)";

$x = explode("neu", $antrag->revision_name);
if (count($x) > 1) {
	$rev_neu = $x[0] . "neu" . ($x[1] + 1);
} else {
	$rev_neu = $antrag->revision_name . "neu";
}

$kann_aes_ablehnen = $antrag->veranstaltung->getEinstellungen()->initiatorInnen_duerfen_aes_ablehnen;

?>
	<script>
		$(function () {
			function recalc_ae_status(val) {
				var $select = $("#ae_select_" + val),
					absaetze = $select.data("absaetze"),
					voll_angenommen = true;
				for (var i in absaetze) {
					if (!$(".absatz_" + absaetze[i]).find("input[type=radio][value=" + val + "]").prop("checked")) voll_angenommen = false;
				}
				if (voll_angenommen) $select.find("option[value=<?php echo IAntrag::$STATUS_ANGENOMMEN; ?>]").prop("selected", true);
				else $select.val("");
			}

			$(".absatz_selector_holder").each(function () {
				var $absatz = $(this),
					$radios = $absatz.find("input[type=radio]");

				$radios.on("click change",function () {
					var curr = $absatz.find("input[type=radio]:checked").val();
					if (curr == "original") {
						$absatz.find("blockquote.original").show();
						$absatz.find("textarea.neu_text").parent().hide();
						$absatz.find("blockquote.aenderung").hide();
					} else if (curr == "neu") {
						$absatz.find("blockquote.original").hide();
						$absatz.find("blockquote.aenderung").hide();
						var $textarea = $absatz.find("textarea.neu_text");
						$textarea.parent().show();
						ckeditor_bbcode($textarea.attr("id"));
					} else {
						$absatz.find("blockquote.original").hide();
						$absatz.find("textarea.neu_text").parent().hide();
						$absatz.find("blockquote.aenderung").hide();
						$absatz.find("blockquote.ae_" + curr).show();
					}
				}).first().change();

				$radios.on("change", function () {
					$absatz.find("input[type=radio]").each(function () {
						var val = $(this).val();
						if (parseInt(val) > 0) recalc_ae_status(val);
					});
				});

				$("input[name=titel_neu]").change(function() {
					$("input[name=titel_typ][value=neu]").prop("checked", true);
				});

			});
		});
	</script>
	<h1 class="well">Überarbeiten: <?php
		if ($antrag->revision_name != "") echo CHtml::encode($antrag->revision_name . ": " . $antrag->name);
		else echo CHtml::encode($antrag->name);
		?></h1>

	<div class="well well_first">
		<form method="POST" action="<?php echo $this->createUrl("antrag/aes_einpflegen", array("antrag_id" => $antrag->id)); ?>">
			<div class="content">
				<label>Neuer Revisionstitel: <input type="text" name="rev_neu" value="<?php echo CHtml::encode($rev_neu); ?>"></label>
			</div>

			<h3>Titel</h3>

			<div class="content">

				<label>
					<input type="radio" name="titel_typ" value="original" checked> Original beibehalten: „<?php echo CHtml::encode($antrag->name); ?>“
				</label>
				<?php foreach ($aenderungsantraege as $ae) if ($ae->name_neu != $antrag->name) {
					$ae_link = CHtml::link($ae->revision_name, $this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $antrag->id, "aenderungsantrag_id" => $ae->id)));
					?>
					<label>
						<input type="radio" name="titel_typ" value="<?php echo CHtml::encode($ae->id); ?>"> <?php echo $ae_link ?> übernehmen: „<?php echo CHtml::encode($ae->name_neu); ?>“
					</label>
				<?
				}
				?>
				<div>
					<label style="display: inline;"><input type="radio" name="titel_typ" value="neu"> Neuer Titel:</label> „<input type="text" name="titel_neu" title="Neuer Titel">“
				</div>

			</div>

			<?php

			$absae = $antrag->getParagraphs(true, true);
			$aes2absaetze = array();
			/** @var Aenderungsantrag[] $aes */
			$aes = array();
			foreach ($absae as $i => $abs) {
				echo "<h3>Absatz " . ($i + 1) . "</h3><div class='absatz_selector_holder content absatz_" . $i . "'>";
				/** @var AntragAbsatz $abs */
				$full_texts = "";
				echo "<label><input type='radio' name='absatz_typ[$i]' value='original' checked> Original beibehalten</label>";

				foreach ($abs->aenderungsantraege as $ant) {
					if (!isset($aes2absaetze[IntVal($ant->id)])) {
						$aes2absaetze[IntVal($ant->id)] = array();
						$aes[IntVal($ant->id)]          = $ant;
					}
					$aes2absaetze[IntVal($ant->id)][] = IntVal($i);

					$par = $ant->getDiffParagraphs();
					if ($par[$i] != "") {
						$ae_link = "<a href=\"" . CHtml::encode($this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $antrag->id, "aenderungsantrag_id" => $ant->id))) . "\" target=\"_blank\">" . CHtml::encode($ant->revision_name) . "</a>";

						$antragstellerInnen = $ant->getAntragstellerInnen();
						$x = array();
						foreach ($antragstellerInnen as $a) $x[] = $a->name;
						$von = implode(", ", $x);

						echo "<label><input type='radio' name='absatz_typ[$i]' value='" . $ant->id . "'> " . $ae_link . " (von " . CHtml::encode($von) . ")</label>";
						$full_texts .= "<blockquote class='aenderung ae_" . $ant->id . "'>" . DiffUtils::renderBBCodeDiff2HTML($abs->str_bbcode, $par[$i]) . "</blockquote>";
					}
				}
				echo "<label><input type='radio' name='absatz_typ[$i]' value='neu'> Neuer Text</label><br>";
				echo "<blockquote class='original'>" . $abs->str_html_plain . "</blockquote>" . $full_texts;
				echo "<div><textarea class='neu_text' name='neu_text[$i]' id='neu_text_$i' style='width: 550px; height: 200px;'>" . CHtml::encode($abs->str_bbcode) . "</textarea></div>";
				echo "</div>";
			}

			echo "<h3>Begründung</h3><div class='absatz_selector_holder content absatz_begruendung'>";
			/** @var AntragAbsatz $abs */
			$full_texts = "";
			echo "<label><input type='radio' name='begruendung_typ' value='original' checked> Original beibehalten</label>";
			echo "<label><input type='radio' name='begruendung_typ' value='neu'> Neuer Text</label><br>";
			echo "<blockquote class='original'>" . HtmlBBcodeUtils::bbcode2html($antrag->begruendung) . "</blockquote>" . $full_texts;
			echo "<div><textarea class='neu_text' name='neu_begruendung' id='neu_begruendung' style='width: 550px; height: 200px;'>" . CHtml::encode($antrag->begruendung) . "</textarea></div>";
			echo "</div>";

			?>
			<h3> Stati der Änderunganträge setzen </h3>

			<div id="ae_status_setter" class="content">
				<?php foreach ($antrag->aenderungsantraege as $ae) if (!in_array($ae->status, IAntrag::$STATI_UNSICHTBAR)) {
					$absaetze = $ae->getAffectedParagraphs();
					?>
					<label><span class="ae"><?php echo CHtml::encode($ae->revision_name); ?></span> <select id="ae_select_<?php echo $ae->id; ?>" name="ae[<?php echo $ae->id; ?>]" required data-absaetze="<?php echo json_encode($absaetze); ?>">
							<option value=""> - bitte auswählen -</option>
							<option value="<?php echo IAntrag::$STATUS_ANGENOMMEN; ?>">Übernommen</option>
							<option value="<?php echo IAntrag::$STATUS_MODIFIZIERT_ANGENOMMEN; ?>">Modifiziert Angenommen</option>
							<option value="<?php echo IAntrag::$STATUS_EINGEREICHT_GEPRUEFT; ?>">Aufrecht erhalten</option>
							<?php if ($kann_aes_ablehnen) { ?>
								<option value="<?php echo IAntrag::$STATUS_ABGELEHNT; ?>">Abgelehnt</option>
							<?php } ?>
						</select></label>
				<?php } ?>
			</div>
			<div class="submitrow content">
				<?php
				$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'primary', 'icon' => 'ok white', 'label' => 'Speichern', 'htmlOptions' => array('name' => AntiXSS::createToken('ueberarbeiten'))));
				?>
			</div>
		</form>
	</div>
<?php
