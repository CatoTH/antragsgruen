<?php

/**
 * @var AntragController $this
 * @var Antrag $antrag
 * @var array|Aenderungsantrag[] $aenderungsantraege
 * @var Sprache $sprache
 */

/** @var CWebApplication $app  */
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

?>
<script>
	$(function() {
		$(".absatz_selector_holder").each(function() {
			var $absatz = $(this);
			$absatz.find("input[type=radio]").on("click change", function() {
				var curr = $absatz.find("input[type=radio]:checked").val();
				if (curr == "original") {
					$absatz.find("blockquote.original").show();
					$absatz.find("textarea.neu_text").hide();
					$absatz.find("blockquote.aenderung").hide();
				} else if (curr == "neu") {
					$absatz.find("blockquote.original").hide();
					$absatz.find("blockquote.aenderung").hide();
					var $textarea = $absatz.find("textarea.neu_text");
					$textarea.show();
					ckeditor_bbcode($textarea.attr("id"));
				} else {
					$absatz.find("blockquote.original").hide();
					$absatz.find("textarea.neu_text").hide();
					$absatz.find("blockquote.aenderung").hide();
					$absatz.find("blockquote.ae_" + curr).show();
				}
			}).first().change();
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
					?>
					<label>
						<input type="radio" name="titel_typ" value="<?php echo CHtml::encode($ae->id); ?>"> <?php echo CHtml::encode($ae->revision_name) ?> übernehmen: „<?php echo CHtml::encode($ae->name_neu); ?>“
					</label>
				<?
				}
				?>
				<label>
					<input type="radio" name="titel_typ" value="neu"> Neuer Titel: „<input type="text" name="titel_neu">“
				</label>

			</div>

			<?php

			$absae = $antrag->getParagraphs(true, true);
			foreach ($absae as $i => $abs) {
				echo "<h3>Absatz " . ($i + 1) . "</h3><div class='absatz_selector_holder content'>";
				/** @var AntragAbsatz $abs */
				$full_texts = "";
				echo "<label><input type='radio' name='absatz_typ[$i]' value='original' checked> Original beibehalten</label>";

				foreach ($abs->aenderungsantraege as $ant) {
					$par = $ant->getDiffParagraphs();
					if ($par[$i] != "") {
						echo "<label><input type='radio' name='absatz_typ[$i]' value='" . $ant->id . "'> " . CHtml::encode($ant->revision_name) . "</label>";
						$full_texts .= "<blockquote class='aenderung ae_" . $ant->id . "'>" . DiffUtils::renderBBCodeDiff2HTML($abs->str_bbcode, $par[$i]) . "</blockquote>";
					}
				}
				echo "<label><input type='radio' name='absatz_typ[$i]' value='neu'> Neuer Text</label>";
				echo "<blockquote class='original'>" . $abs->str_html_plain . "</blockquote>" . $full_texts;
				echo "<textarea class='neu_text' name='neu_text[$i]' id='neu_text_$i' style='width: 550px; height: 200px;'>" . CHtml::encode($abs->str_bbcode) . "</textarea>";
				echo "</div>";
			}
			?>

			<div class="submitrow content">
				<?php
				$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'primary', 'icon' => 'ok white', 'label' => 'Speichern'));
				?>
			</div>
		</form>
	</div>
<?php
