<?php

/**
 * @var AntragController $this
 * @var Antrag $antrag
 * @var array|Person[] $antragstellerinnen
 * @var array|Person[] $unterstuetzerinnen
 * @var array|Person[] $ablehnung_von
 * @var array|Person[] $zustimmung_von
 * @var array|Aenderungsantrag[] $aenderungsantraege
 * @var bool $js_protection
 * @var array $hiddens
 * @var bool $edit_link
 * @var array $kommentare_offen
 * @var string $komm_del_link
 * @var string|null $admin_edit
 * @var Person $kommentar_person
 * @var bool $support_form
 * @var string $support_status
 * @var Sprache $sprache
 */

$this->breadcrumbs         = array(
	CHtml::encode($antrag->veranstaltung0->name_kurz) => $this->createUrl("site/veranstaltung"),
	$sprache->get("Antrag"),
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");

$this->pageTitle = $antrag->nameMitRev() . " (" . $antrag->veranstaltung0->name . ", Antragsgrün)";

Yii::app()->getClientScript()->registerScriptFile(Yii::app()->request->baseUrl . '/js/socialshareprivacy/jquery.socialshareprivacy.min.js');


$rows = 4;
if ($antrag->datum_beschluss != "") $rows++;
if (count($antrag->antraege) > 0) $rows++;

$html = '<ul class="funktionen">';
// $html .= '<li class="unterstuetzen"><a href="#">Antrag unterstützen</a></li>';
if ($antrag->veranstaltung0->darfEroeffnenAenderungsAntrag()) $html .= '<li class="aender-stellen">' . CHtml::link($sprache->get("Änderungsantrag stellen"), $this->createUrl("aenderungsantrag/neu", array("antrag_id" => $antrag->id))) . '</li>';
$html .= '<li class="download">' . CHtml::link($sprache->get("PDF-Version herunterladen"), $this->createUrl("antrag/pdf", array("antrag_id" => $antrag->id))) . '</li>';
if ($edit_link) $html .= '<li class="edit">' . CHtml::link($sprache->get("Antrag bearbeiten"), $this->createUrl("antrag/bearbeiten", array("antrag_id" => $antrag->id))) . '</li>';

if ($admin_edit) $html .= '<li class="admin_edit">' . CHtml::link("Admin: bearbeiten", $admin_edit) . '</li>';
else $html .= '<li class="zurueck">' . CHtml::link("Zurück zur Übersicht", $this->createUrl("site/veranstaltung")) . '</a></li>
					</ul>';
$this->menus_html[] = $html;

?>
<h1 class="well"><?php
	if ($antrag->revision_name != "") echo CHtml::encode($antrag->revision_name . ": " . $antrag->name);
	else echo CHtml::encode($antrag->name);
	?></h1>

<div class="antragsdaten well" style="min-height: 114px;">
	<div id="socialshareprivacy"></div>
	<script>
		$(function ($) {
			$('#socialshareprivacy').socialSharePrivacy({
				css_path:"/js/socialshareprivacy/socialshareprivacy/socialshareprivacy.css"
			});
		});
	</script>
	<div class="content">
		<table style="width: 100%;" class="antragsdaten">
			<tr>
				<th><?=$sprache->get("Veranstaltung")?>:</th>
				<td><?php
					echo CHtml::link(CHtml::encode($antrag->veranstaltung0->name), array('/veranstaltung/anzeige/?id=' . $antrag->veranstaltung));
					?></td>
			</tr>
			<tr>
				<th><?=$sprache->get("AntragsstellerIn")?>:</th>
				<td><?php
					foreach ($antragstellerinnen as $a) {
						echo CHtml::encode($a->name);
					}
					?></td>
			</tr>
			<tr>
				<th>Status:</th>
				<td><?php
					echo CHtml::encode(IAntrag::$STATI[$antrag->status]);
					?></td>
			</tr>
			<?php if ($antrag->datum_beschluss != "") { ?>
			<tr>
				<th>Beschlossen am:</th>
				<td><?php
					echo HtmlBBcodeUtils::formatMysqlDate($antrag->datum_beschluss);
					?></td>
			</tr>
			<?php } ?>
			<tr>
				<th>Eingereicht:</th>
				<td><?php
					echo HtmlBBcodeUtils::formatMysqlDateTime($antrag->datum_einreichung);
					?></td>
			</tr>
			<?php if (count($antrag->antraege) > 0) { ?>
			<tr>
				<th>Ersetzt den Antrag:</th>
				<td>
					<?php foreach ($antrag->antraege as $a) {
					echo CHtml::link($a->revision_name . " - " . $a->name, $this->createUrl("antrag/anzeige", array("antrag_id" => $a->id)));
				} ?>
				</td>
			</tr>
			<?php } ?>
		</table>

		<div class="hidden-desktop">
			<div style="width: 49%; display: inline-block; text-align: center; padding-top: 25px;">
				<a href="<?=CHtml::encode($this->createUrl("antrag/pdf", array("antrag_id" => $antrag->id)))?>" class="btn" type="button" style="color: black;"><i class="icon-pdf"></i> PDF-Version</a>
			</div>
			<div style="width: 49%; display: inline-block; text-align: center; padding-top: 25px;">
				<a href="<?=CHtml::encode($this->createUrl("aenderungsantrag/neu", array("antrag_id" => $antrag->id)))?>" class="btn btn-danger" type="button" style="color: white;"><i class="icon-aender-stellen"></i> <?=CHtml::encode($sprache->get("Änderungsantrag stellen"))?></a>
			</div>
		</div>
	</div>
</div>

<div class="antrags_text_holder well">
<h3><?=$sprache->get("Antragstext")?></h3>

<div class="textholder consolidated">
<?php
$dummy_komm = new AntragKommentar();

$absae = $antrag->getParagraphs(true, true);



foreach ($absae as $i => $abs) {
	/** @var AntragAbsatz $abs */

	$kommoffenclass = (!in_array($i, $kommentare_offen) ? "kommentare_closed_absatz" : "");
	?>
<div class='row-fluid row-absatz <?php echo $kommoffenclass; ?>' data-absatznr='<?php echo $i; ?>'>

<div class="textabschnitt">
	<div class="absatz_text orig antragabsatz_holder antrags_text_holder_nummern">
		<?php echo $abs->str_html; ?>

	</div>

	<?php

	foreach ($abs->aenderungsantraege as $ant) {
		$par = $ant->getDiffParagraphs();
		if ($par[$i] != "") {
			?>
			<div class="absatz_text ae_<?php echo $ant->id; ?> antragabsatz_holder antrags_text_holder_nummern"
					style="display: none; position: relative; border-right: solid 1px lightgray; margin-left: 0;">
				<?php
				echo DiffUtils::renderBBCodeDiff2HTML($abs->str_bbcode, $par[$i]);
				?>
			</div>
			<?php
		}
	}

	if (count($abs->aenderungsantraege) > 0) {
		?>
		<div class='aenders'>
			<ul>
				<?php
				/** @var Aenderungsantrag $ant */
				foreach ($abs->aenderungsantraege as $ant) {
					$ae_link = $this->createUrl("aenderungsantrag/anzeige", array("veranstaltung_id" => $ant->antrag->veranstaltung0->yii_url, "antrag_id" => $ant->antrag->id, "aenderungsantrag_id" => $ant->id));
					echo "<li><a class='aender_link' data-id='" . $ant->id . "' href='" . CHtml::encode($ae_link) . "'>" . CHtml::encode($ant->revision_name) . "</a></li>\n";
				} ?>
			</ul>
		</div>
		<?php
	}

	if (count($abs->kommentare) > 0 || $antrag->veranstaltung0->darfEroeffnenKommentar()) {
	?>
	<div class='kommentare'><?
		?>
		<a href='#' class='shower'><?php echo count($abs->kommentare); ?></a>
		<a href='#' class='hider'><?php echo count($abs->kommentare); ?></a>
	</div>
	<? } ?>
</div>
	<?php

	/** @var AntragKommentar $komm */
	foreach ($abs->kommentare as $komm) {
		?>
	<div class="kommentarform well" id="komm<?php echo $komm->id; ?>">
		<div class="datum"><?php echo HtmlBBcodeUtils::formatMysqlDateTime($komm->datum)?></div>
		<h3>Kommentar von <?php echo CHtml::encode($komm->verfasser->name); ?></h3>
		<?php
		echo nl2br(CHtml::encode($komm->text));
		if (!is_null($komm_del_link) && $komm->kannLoeschen(Yii::app()->user)) echo "<div class='del_link'><a href='" . CHtml::encode(str_replace("#komm_id#", $komm->id, $komm_del_link)) . "'>x</a></div>";
		?>
	</div>
		<?php
	}

	if ($antrag->veranstaltung0->darfEroeffnenKommentar()) {
		/** @var TbActiveForm $form */
		$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
			'type'        => 'horizontal',
			"htmlOptions" => array(
				"class" => "kommentarform well",
			),
		));
		?>
	<fieldset>
		<legend>Kommentar schreiben</legend>

		<?php

		if ($js_protection) {
			?>
			<div class="js_protection_hint">ACHTUNG: Um diese Funktion zu nutzen, muss entweder JavaScript aktiviert sein, oder du musst eingeloggt sein.</div>
			<?php
		}
		foreach ($hiddens as $name => $value) {
			echo '<input type="hidden" name="' . CHtml::encode($name) . '" value="' . CHtml::encode($value) . '">';
		}
		echo '<input type="hidden" name="absatz_nr" value="' . $abs->absatz_nr . '">';

		echo $form->textFieldRow($kommentar_person, 'name', array("id" => "Person_name_" . $i, 'labelOptions' => array("for" => "Person_name_" . $i, 'label' => 'Name')));
		echo $form->textFieldRow($kommentar_person, 'email', array("id" => "Person_email_" . $i, 'labelOptions' => array("for" => "Person_email_" . $i, 'label' => 'E-Mail')));
		?>
		<div class="control-group "><label class="control-label" for="AntragKommentar_text_<?=$i?>">Kommentar:</label>

			<div class="controls">
				<?php echo $form->textArea($dummy_komm, "text", array("id" => "AntragKommentar_text_" . $i)); ?>
			</div>
		</div>

	</fieldset>

	<div>
		<?php
		$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'primary', 'icon' => 'ok white', 'label' => 'Kommentar abschicken'));
		?>
	</div>
		<?php
		$this->endWidget();
	}
	?>

</div>
	<?php
}
?>
</div>
</div>

<?php if (trim($antrag->begruendung) != "") { ?>

<div class="begruendungs_text_holder well">
	<h3>Begründung</h3>

	<div class="textholder consolidated">
		<?php echo HtmlBBcodeUtils::bbcode2html($antrag->begruendung) ?>
	</div>
</div>

<? } ?>

<div class="well">
	<h2>UnterstützerInnen</h2>

	<div class="content">
		<?php
		$curr_user_id = (Yii::app()->user->isGuest ? 0 : Yii::app()->user->getState("person_id"));

		echo "<strong>UnterstützerInnen:</strong><br>";
		if (count($unterstuetzerinnen) > 0) {
			echo CHtml::openTag('ul');
			foreach ($unterstuetzerinnen as $p) {
				echo CHtml::openTag('li');
				if ($p->id == $curr_user_id) echo '<span class="label label-info">Du!</span> ';
				echo CHtml::encode($p->name);
				echo CHtml::closeTag('li');
			}
			echo CHtml::closeTag('ul');
		} else echo '<em>keine</em><br>';
		echo "<br>";

		if (count($zustimmung_von) > 0) {
			echo "<strong>Zustimmung von:</strong><br>";
			echo CHtml::openTag('ul');
			foreach ($zustimmung_von as $p) {
				echo CHtml::openTag('li');
				if ($p->id == $curr_user_id) echo '<span class="label label-info">Du!</span> ';
				echo CHtml::encode($p->name);
				echo CHtml::closeTag('li');
			}
			echo CHtml::closeTag('ul');
			echo "<br>";
		}

		if (count($ablehnung_von) > 0) {
			echo "<strong>Abgelehnt von:</strong><br>";
			echo CHtml::openTag('ul');
			foreach ($ablehnung_von as $p) {
				echo CHtml::openTag('li');
				if ($p->id == $curr_user_id) echo '<span class="label label-info">Du!</span> ';
				echo CHtml::encode($p->name);
				echo CHtml::closeTag('li');
			}
			echo CHtml::closeTag('ul');
			echo "<br>";
		}
		?>
	</div>

	<?php
	if ($support_form) {
		$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
			'type'        => 'inline',
			'htmlOptions' => array('class' => 'well'),
		));
		echo "<div style='text-align: center; margin-bottom: 20px;'>";
		switch ($support_status) {
			case IUnterstuetzer::$ROLLE_INITIATOR:
				break;
			case IUnterstuetzer::$ROLLE_MAG:
				$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'label' => 'Zurückziehen', 'icon' => 'icon-remove', 'htmlOptions' => array('name' => AntiXSS::createToken('dochnicht'))));
				break;
			case IUnterstuetzer::$ROLLE_MAG_NICHT:
				$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'label' => 'Zurückziehen', 'icon' => 'icon-remove', 'htmlOptions' => array('name' => AntiXSS::createToken('dochnicht'))));
				break;
			default:
				?>
					<div style="display: inline-block; width: 49%; text-align: center;">
						<?php
						$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'success', 'label' => 'Zustimmen', 'icon' => 'icon-thumbs-up', 'htmlOptions' => array('name' => AntiXSS::createToken('mag'))));
						?>
					</div>
					<!--
					<div style="display: inline-block; width: 49%; text-align: center;">
						<?php
					$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'danger', 'label' => 'Ablehnen', 'icon' => 'icon-thumbs-down', 'htmlOptions' => array('name' => AntiXSS::createToken('magnicht'))));
					?>
					</div>
					-->
					<?php
		}
		echo "</div>";
		$this->endWidget();
	} else {
		Yii::app()->user->setFlash('warning', 'Um diesen Antrag unterstützen zu können, musst du ' . CHtml::link("dich einzuloggen", $this->createUrl("site/login")) . '</a>.');
		$this->widget('bootstrap.widgets.TbAlert', array(
			'block' => true,
			'fade'  => true,
		));
	} ?>
</div>

<div class="well">
	<h2><?=$sprache->get("Änderungsanträge")?></h2>

	<div class="content">
		<?php
		if (count($aenderungsantraege) > 0) {
			echo CHtml::openTag('ul', array("class" => "aenderungsantraege"));
			foreach ($aenderungsantraege as $relatedModel) {
				echo CHtml::openTag('li');
				$aename = $relatedModel->revision_name;
				if ($aename == "") $aename = $relatedModel->id;
				echo CHtml::link($aename, $this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $antrag->id, "aenderungsantrag_id" => $relatedModel->id)));
				echo " (" . CHtml::encode(Aenderungsantrag::$STATI[$relatedModel->status]) . ")";
				echo CHtml::closeTag('li');
			}
			echo CHtml::closeTag('ul');
		} else echo '<em>keine</em>';

		?></div>
</div>
