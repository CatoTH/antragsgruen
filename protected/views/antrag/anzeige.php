<?php

/**
 * @var AntragController $this
 * @var Antrag $antrag
 * @var array|Aenderungsantrag[] $aenderungsantraege
 * @var bool $js_protection
 * @var array $hiddens
 * @var bool $edit_link
 * @var array $kommentare_offen
 * @var string $komm_del_link
 * @var string|null $admin_edit
 * @var Person $kommentar_person
 * @var string $support_status
 * @var Sprache $sprache
 */

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung->name_kurz) => $this->createUrl("veranstaltung/index"),
	$sprache->get("Antrag"),
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");

$this->pageTitle = $antrag->nameMitRev() . " (" . $antrag->veranstaltung->name . ", Antragsgrün)";

/** @var CWebApplication $app */
$app = Yii::app();
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/socialshareprivacy/jquery.socialshareprivacy.js');

$rows = 4;
if ($antrag->datum_beschluss != "") $rows++;
if (count($antrag->antraege) > 0) $rows++;

$html = '<ul class="funktionen">';
// $html .= '<li class="unterstuetzen"><a href="#">Antrag unterstützen</a></li>';

$policy = $antrag->veranstaltung->getPolicyAenderungsantraege();
if ($policy->checkCurUserHeuristically()) $html .= '<li class="aender-stellen">' . CHtml::link($sprache->get("Änderungsantrag stellen"), $this->createUrl("aenderungsantrag/neu", array("antrag_id" => $antrag->id))) . '</li>';
else {
	$msg = $policy->getPermissionDeniedMsg();
	if ($msg != "") $html .= '<li class="aender-stellen"><span><span style="font-style: italic;">' . CHtml::encode($sprache->get("Änderungsantrag stellen")) . "</span><br><span style='font-size: 13px; color: #dbdbdb; text-transform: none;'>" . CHtml::encode($policy->getPermissionDeniedMsg()) . '</span></span></li>';
}

if ($antrag->veranstaltung->getEinstellungen()->kann_pdf) $html .= '<li class="download">' . CHtml::link($sprache->get("PDF-Version herunterladen"), $this->createUrl("antrag/pdf", array("antrag_id" => $antrag->id))) . '</li>';
if ($edit_link) {
	$html .= '<li class="edit">' . CHtml::link($sprache->get("Änderungsanträge einpflegen"), $this->createUrl("antrag/aes_einpflegen", array("antrag_id" => $antrag->id))) . '</li>';
	$html .= '<li class="edit">' . CHtml::link($sprache->get("Antrag bearbeiten"), $this->createUrl("antrag/aendern", array("antrag_id" => $antrag->id))) . '</li>';
}

if ($admin_edit) $html .= '<li class="admin_edit">' . CHtml::link("Admin: bearbeiten", $admin_edit) . '</li>';
else $html .= '<li class="zurueck">' . CHtml::link("Zurück zur Übersicht", $this->createUrl("veranstaltung/index")) . '</li>
					</ul>';
$this->menus_html[] = $html;

?>
<h1><?php echo CHtml::encode($antrag->nameMitRev()); ?></h1>

<div class="antragsdaten"
	 style="min-height: <?php if ($antrag->veranstaltung->getEinstellungen()->ansicht_minimalistisch && Yii::app()->user->isGuest) echo "60"; else echo "114"; ?>px;">
	<div id="socialshareprivacy"></div>
	<?php if (!$antrag->veranstaltung->getEinstellungen()->ansicht_minimalistisch) { ?>

		<div class="content">
			<?php if (count($antrag->antraege) > 0) { ?>
				<div class="alert alert-error" style="margin-top: 10px; margin-bottom: 25px;">
                    <?php if (count($antrag->antraege) == 1) {
                        echo 'Achtung: dies ist eine alte Fassung; die aktuelle Fassung gibt es hier:<br>';
                        $a = $antrag->antraege[0];
                        echo CHtml::link($a->revision_name . " - " . $a->name, $this->createUrl("antrag/anzeige", array("antrag_id" => $a->id)));
					} else {
                        echo 'Achtung: dies ist eine alte Fassung. Aktuellere Fassungen gibt es hier:<br>';
                        foreach ($antrag->antraege as $a) {
                            echo "- " . CHtml::link($a->revision_name . " - " . $a->name, $this->createUrl("antrag/anzeige", array("antrag_id" => $a->id))) . "<br>";
                        }
                    } ?>
				</div>
			<?php } ?>

			<table style="width: 100%;" class="antragsdaten">
				<tr>
					<th><?= $sprache->get("Veranstaltung") ?>:</th>
					<td><?php
						echo CHtml::link($antrag->veranstaltung->name, $this->createUrl("veranstaltung/index"));
						?></td>
				</tr>
				<tr>
					<th><?= $sprache->get("AntragsstellerIn") ?>:</th>
					<td><?php
						$x = array();
						foreach ($antrag->getAntragstellerInnen() as $a) {
							$x[] = CHtml::encode($a->getNameMitOrga());
						}
						echo implode(", ", $x);
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
						<th>Entschieden am:</th>
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
				<?php if ($antrag->abgeleitetVon) { ?>
					<tr>
						<th>Ersetzt diesen Antrag:</th>
						<td><?php echo CHtml::link($antrag->abgeleitetVon->revision_name . " - " . $antrag->abgeleitetVon->name, $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->abgeleitetVon->id))); ?> </td>
					</tr>
				<?php } ?>
			</table>

			<div class="hidden-desktop">
				<div style="width: 49%; display: inline-block; text-align: center; padding-top: 25px;">
					<a href="<?= CHtml::encode($this->createUrl("antrag/pdf", array("antrag_id" => $antrag->id))) ?>"
					   class="btn" style="color: black;"><i class="icon-pdf"></i> PDF-Version</a>
				</div>
				<?
				$policy = $antrag->veranstaltung->getPolicyAenderungsantraege();
				if ($policy->checkCurUserHeuristically()) {
					?>
					<div style="width: 49%; display: inline-block; text-align: center; padding-top: 25px;">
						<a href="<?= CHtml::encode($this->createUrl("aenderungsantrag/neu", array("antrag_id" => $antrag->id))) ?>"
						   class="btn btn-danger" style="color: white;"><i
								class="icon-aender-stellen"></i> <?= CHtml::encode($sprache->get("Änderungsantrag stellen")) ?>
						</a>
					</div>
				<? } ?>
			</div>
		</div>
	<?php
	}
	/*
	$this->widget('bootstrap.widgets.TbAlert', array(
		'block' => true,
		'fade'  => true,
	));
	*/
	?>
</div>

<div
	class="antrags_text_holder<?php if ($antrag->veranstaltung->getEinstellungen()->zeilenlaenge > 80) echo " kleine_schrift"; ?>">
	<h3><?= $sprache->get("Antragstext") ?></h3>

	<?php
	$dummy_komm = new AntragKommentar();

	$absae = $antrag->getParagraphs(true, true);


	foreach ($absae as $i => $abs) {
		/** @var AntragAbsatz $abs */


		$classes = "";
		if (!in_array($i, $kommentare_offen)) $classes .= " kommentare_closed_absatz";
		?>
		<div class='row-fluid row-absatz<?php echo $classes; ?>' data-absatznr='<?php echo $i; ?>'>
			<ul class="lesezeichen">
				<?php
				if (count($abs->kommentare) > 0 || $antrag->veranstaltung->darfEroeffnenKommentar()) {
					?>
					<li class='kommentare'><?
						?>
						<a href='#' class='shower'><?php echo count($abs->kommentare); ?></a>
						<a href='#' class='hider'><?php echo count($abs->kommentare); ?></a>
					</li>
				<?
				}

				/** @var Aenderungsantrag $ant */
				foreach ($abs->aenderungsantraege as $ant) {
					$ae_link = $this->createUrl("aenderungsantrag/anzeige", array("veranstaltung_id" => $ant->antrag->veranstaltung->url_verzeichnis, "antrag_id" => $ant->antrag->id, "aenderungsantrag_id" => $ant->id));
					echo "<li class='aenderungsantrag' data-first-line='" . $ant->getFirstAffectedLineOfParagraph_absolute($i, $absae) . "'><a class='aender_link' data-id='" . $ant->id . "' href='" . CHtml::encode($ae_link) . "'>" . CHtml::encode($ant->revision_name) . "</a></li>\n";
				} ?>
			</ul>

			<div class="absatz_text orig antrags_text_holder_nummern">
				<?php echo $abs->str_html; ?>

			</div>

			<?php

			foreach ($abs->aenderungsantraege as $ant) {
				$par = $ant->getDiffParagraphs();
				if ($par[$i] != "") {
					?>
					<div
						class="absatz_text diff ae_<?php echo $ant->id; ?>" style="display: none; position: relative; border-right: solid 1px lightgray; margin-left: 0;">
						<?php
						echo DiffUtils::renderBBCodeDiff2HTML($abs->str_bbcode, $par[$i]);
						?>
					</div>
				<?php
				}
			}

			/** @var AntragKommentar $komm */
			foreach ($abs->kommentare as $komm) {
				$komm_link = $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id));
				?>
				<div class="kommentarform" id="komm<?php echo $komm->id; ?>">
					<div class="datum"><?php echo HtmlBBcodeUtils::formatMysqlDateTime($komm->datum) ?></div>
					<h3>Kommentar von
						<?php echo CHtml::encode($komm->verfasserIn->getNameMitOrga());
						if ($komm->status == IKommentar::$STATUS_NICHT_FREI) echo " <em>(noch nicht freigeschaltet)</em>";
						?></h3>
					<?php
					echo nl2br(CHtml::encode($komm->text));
					if (!is_null($komm_del_link) && $komm->kannLoeschen(Yii::app()->user)) echo "<div class='del_link'><a href='" . CHtml::encode(str_replace(rawurlencode("#komm_id#"), $komm->id, $komm_del_link)) . "'>x</a></div>";

					if ($komm->status == IKommentar::$STATUS_NICHT_FREI && $antrag->veranstaltung->isAdminCurUser()) {
						$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
							'type'        => 'inline',
							'htmlOptions' => array('class' => '', "style" => "clear: both;"),
							'action'      => $komm_link
						));
						echo '<div style="display: inline-block; width: 49%; text-align: center;">';
						$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'success', 'label' => 'Freischalten', 'icon' => 'icon-thumbs-up', 'htmlOptions' => array('name' => AntiXSS::createToken('komm_freischalten'))));
						echo '</div><div style="display: inline-block; width: 49%; text-align: center;">';
						$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'danger', 'label' => 'Löschen', 'icon' => 'icon-thumbs-down', 'htmlOptions' => array('name' => AntiXSS::createToken('komm_nicht_freischalten'))));
						echo '</div>';
						$this->endWidget();
					}

					?>
					<div class="kommentar_bottom">
						<div
							class="kommentarlink"><?php echo CHtml::link("Kommentar verlinken", $komm_link); ?></div>
						<?php
						if ($this->veranstaltung->getEinstellungen()->kommentare_unterstuetzbar) {
							$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
								'type'        => 'inline',
								'htmlOptions' => array('class' => 'kommentar_unterstuetzerInnen_holder'),
								'action'      => $komm_link,
							));

							$meine_unterstuetzung = AntragKommentarUnterstuetzerInnen::meineUnterstuetzung($komm->id);

							$anzahl_dafuer = $anzahl_dagegen = 0;
							foreach ($komm->unterstuetzerInnen as $unt) {
								if ($unt->dafuer) $anzahl_dafuer++;
								else $anzahl_dagegen++;
							}
							if ($meine_unterstuetzung !== null) {
								?>
								<span class="dafuer"><span
										class="icon-thumbs-up"></span> <?php echo $anzahl_dafuer; ?></span>
								<span class="dagegen"><span
										class="icon-thumbs-down"></span> <?php echo $anzahl_dagegen; ?></span>
								<span class="meine">
						<span class="momentan"><?php
							if ($meine_unterstuetzung->dafuer) echo '<span class="icon-thumbs-up"></span> Du hast diesen Kommentar positiv bewertet';
							else echo '<span class="icon-thumbs-down"></span> Du hast diesen Kommentar negativ bewertet';
							?></span>
					<button class="dochnicht" type="submit"
							name="<?php echo AntiXSS::createToken('komm_dochnicht'); ?>">Bewertung zurücknehmen
					</button>
					</span>
							<?php } else { ?>
								<button class="dafuer" type="submit"
										name="<?php echo AntiXSS::createToken('komm_dafuer'); ?>"><span
										class="icon-thumbs-up"></span> <?php echo $anzahl_dafuer; ?></button>
								<button class="dagegen" type="submit"
										name="<?php echo AntiXSS::createToken('komm_dagegen'); ?>"><span
										class="icon-thumbs-down"></span> <?php echo $anzahl_dagegen; ?></button>
							<?php
							}
							$this->endWidget();
						}
						?>
					</div>
				</div>
			<?php
			}

			if ($antrag->veranstaltung->darfEroeffnenKommentar()) {
				/** @var CActiveForm $form */
				$form = $this->beginWidget('CActiveForm', array(
					"htmlOptions" => array(
						"class" => "kommentarform",
					),
				));
				?>
				<fieldset>
					<legend>Kommentar schreiben</legend>

					<?php

					if ($js_protection) {
						?>
						<div class="js_protection_hint">ACHTUNG: Um diese Funktion zu nutzen, muss entweder
							JavaScript
							aktiviert sein, oder du musst eingeloggt sein.
						</div>
					<?php
					}
					foreach ($hiddens as $name => $value) {
						echo '<input type="hidden" name="' . CHtml::encode($name) . '" value="' . CHtml::encode($value) . '">';
					}
					echo '<input type="hidden" name="absatz_nr" value="' . $abs->absatz_nr . '">';
					?>
					<div class="row">
						<?php echo $form->labelEx($kommentar_person, 'name'); ?>
						<?php echo $form->textField($kommentar_person, 'name') ?>
					</div>
					<div class="row">
						<?php echo $form->labelEx($kommentar_person, 'email'); ?>
						<?php echo $form->emailField($kommentar_person, 'email') ?>
					</div>
					<div class="row">
						<?php echo $form->labelEx($dummy_komm, 'text'); ?>
						<?php echo $form->textArea($dummy_komm, 'text') ?>
					</div>
				</fieldset>

				<div class="submitrow">
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

<?php if (trim($antrag->begruendung) != "") { ?>

	<div class="begruendungs_text_holder">
		<h3>Begründung</h3>

		<div class="textholder consolidated content">
			<?php echo HtmlBBcodeUtils::bbcode2html($antrag->begruendung) ?>
		</div>
	</div>

<? } ?>

<?php



$unterstuetzerInnen = $antrag->getUnterstuetzerInnen();
$zustimmung_von = $antrag->getZustimmungen();
$ablehnung_von = $antrag->getAblehnungen();
$eintraege = (count($unterstuetzerInnen) > 0 || count($zustimmung_von) > 0 || count($ablehnung_von) > 0);
$unterstuetzen_policy = $antrag->veranstaltung->getPolicyUnterstuetzen();
$kann_unterstuetzen = $unterstuetzen_policy->checkCurUserHeuristically();
$kann_nicht_unterstuetzen_msg = $unterstuetzen_policy->getPermissionDeniedMsg();

if ($eintraege || $kann_unterstuetzen || $kann_nicht_unterstuetzen_msg != "") {
	?>

	<h2>UnterstützerInnen</h2>

	<div class="content">
		<?php
		$curr_user_id = (Yii::app()->user->isGuest ? 0 : Yii::app()->user->getState("person_id"));

		echo "<strong>UnterstützerInnen:</strong><br>";
		if (count($unterstuetzerInnen) > 0) {
			echo CHtml::openTag('ul');
			foreach ($unterstuetzerInnen as $p) {
				echo CHtml::openTag('li');
				if ($p->id == $curr_user_id) echo '<span class="label label-info">Du!</span> ';
				echo CHtml::encode($p->getNameMitOrga());
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
				echo CHtml::encode($p->getNameMitOrga());
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
				echo CHtml::encode($p->getNameMitOrga());
				echo CHtml::closeTag('li');
			}
			echo CHtml::closeTag('ul');
			echo "<br>";
		}
		?>
	</div>

	<?php
	if ($kann_unterstuetzen) {
		$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
			'type' => 'inline'
		));
		echo "<div style='text-align: center; margin-bottom: 20px;'>";
		switch ($support_status) {
			case IUnterstuetzerInnen::$ROLLE_INITIATORIN:
				break;
			case IUnterstuetzerInnen::$ROLLE_MAG:
				$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'label' => 'Zurückziehen', 'icon' => 'icon-remove', 'htmlOptions' => array('name' => AntiXSS::createToken('dochnicht'))));
				break;
			case IUnterstuetzerInnen::$ROLLE_MAG_NICHT:
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
		/*
		Yii::app()->user->setFlash('warning', 'Um diesen Antrag unterstützen zu können, musst du ' . CHtml::link("dich einzuloggen", $this->createUrl("veranstaltung/login")) . '.');
		$this->widget('bootstrap.widgets.TbAlert', array(
			'block' => true,
			'fade'  => true,
		));
		*/
		if ($kann_nicht_unterstuetzen_msg != "") {
			Yii::app()->user->setFlash('warning', $kann_nicht_unterstuetzen_msg);
			$this->widget('bootstrap.widgets.TbAlert', array(
				'block' => true,
				'fade'  => true,
			));
		}
	} ?>
<?php
}

if (count($aenderungsantraege) > 0 || $antrag->veranstaltung->policy_aenderungsantraege != "Admins") {
	?>
	<h2><?= $sprache->get("Änderungsanträge") ?></h2>

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
<?php
}

?>
<script>
	$('#socialshareprivacy').socialSharePrivacy({
		css_path: "/socialshareprivacy/socialshareprivacy.css"
	});

	$(".absatz_text.orig .text .zeilennummer").each(function () {
		$(this).attr("data-zeilennummer", $(this).text());
	});
	$(".row-absatz").each(function () {
		var $absatz = $(this);
		$absatz.find("ul.lesezeichen li.aenderungsantrag").each(function () {
			var $aenderungsantrag = $(this),
				marker_offset = $aenderungsantrag.offset().top,
				first_line = $aenderungsantrag.data("first-line"),
				$lineel = $absatz.find(".zeilennummer[data-zeilennummer=" + first_line + "]");
			if ($lineel.length == 0) {
				// Ergänzung am Ende des Absatzes
				$lineel = $absatz.find(".zeilennummer").last();
			}
			var lineel_offset = $lineel.offset().top;
			if ((marker_offset + 10) < lineel_offset) $aenderungsantrag.css("margin-top", (lineel_offset - (marker_offset + 10)) + "px");
		});
	});
</script>
