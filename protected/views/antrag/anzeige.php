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

$this->breadcrumbs         = array(
	CHtml::encode($antrag->veranstaltung->name_kurz) => $this->createUrl("veranstaltung/index"),
	$sprache->get("Antrag"),
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");

$this->pageTitle = $antrag->nameMitRev() . " (" . $antrag->veranstaltung->name . ", Antragsgrün)";

if ($this->veranstaltung->id == 149 && $antrag->typ == Antrag::$TYP_RESOLUTION) $this->magenta_layout = true;

/** @var CWebApplication $app */
$app = Yii::app();
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/socialshareprivacy/jquery.socialshareprivacy.js');

$rows = 4;
if ($antrag->datum_beschluss != "") $rows++;
if (count($antrag->antraege) > 0) $rows++;

$html = '<ul class="funktionen">';
// $html .= '<li class="unterstuetzen"><a href="#">Antrag unterstützen</a></li>';

$policy = $antrag->veranstaltung->getPolicyAenderungsantraege();

$changeable = $antrag->status != IAntrag::$STATUS_UNVERAENDERLICH;
if ($changeable) {
   if ($policy->checkCurUserHeuristically()) $html .= '<li class="aender-stellen">' . CHtml::link($sprache->get("Änderungsantrag stellen"), $this->createUrl("aenderungsantrag/neu", array("antrag_id" => $antrag->id))) . '</li>';
   else {
	$msg = $policy->getPermissionDeniedMsg();
	if ($msg != "") $html .= '<li class="aender-stellen"><span><span style="font-style: italic;">' . CHtml::encode($sprache->get("Änderungsantrag stellen")) . "</span><br><span style='font-size: 13px; color: #dbdbdb; text-transform: none;'>" . CHtml::encode($policy->getPermissionDeniedMsg()) . '</span></span></li>';
	}
}

$pdf = ($antrag->veranstaltung->getEinstellungen()->kann_pdf && $antrag->status != IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT);

if ($pdf) $html .= '<li class="download">' . CHtml::link($sprache->get("PDF-Version herunterladen"), $this->createUrl("antrag/pdf", array("antrag_id" => $antrag->id))) . '</li>';
if ($edit_link) {
    if ($changeable && count($antrag->aenderungsantraege) > 0) {
        $html .= '<li class="edit">' . CHtml::link($sprache->get("Änderungsanträge einpflegen"), $this->createUrl("antrag/aes_einpflegen", array("antrag_id" => $antrag->id))) . '</li>';
    }
	$html .= '<li class="edit">' . CHtml::link($sprache->get("Antrag bearbeiten"), $this->createUrl("antrag/aendern", array("antrag_id" => $antrag->id))) . '</li>';
}

if ($admin_edit) $html .= '<li class="admin_edit">' . CHtml::link("Admin: bearbeiten", $admin_edit) . '</li>';
else $html .= '<li class="zurueck">' . CHtml::link("Zurück zur Übersicht", $this->createUrl("veranstaltung/index")) . '</li>';
$html .= '</ul>';
$this->menus_html[] = $html;


$spezielle_kommentare = array(AntragKommentar::ABSATZ_ANTRAG => array(), AntragKommentar::ABSATZ_BEGRUENDUNG => array(), AntragKommentar::ABSATZ_TEXT2 => array());
foreach ($antrag->antragKommentare as $komm) if ($komm->absatz < 0 && $komm->status != IKommentar::$STATUS_GELOESCHT) {
    if (isset($spezielle_kommentare[$komm->absatz])) {
        $spezielle_kommentare[$komm->absatz][] = $komm;
    }
}


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
						foreach ($antrag->antragUnterstuetzerInnen as $unt) if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) {
							$name = $unt->getNameMitBeschlussdatum(true);
							if ($unt->person->istWurzelwerklerIn()) {
								$name .= ' (<a href="https://wurzelwerk.gruene.de/web/' . CHtml::encode($unt->person->getWurzelwerkName()) . '">Wurzelwerk-Profil</a>)';
							}
							if ($antrag->veranstaltung->isAdminCurUser() && ($unt->person->email != "" || $unt->person->telefon != "")) {
								$name .= " <small>(Kontaktdaten, nur als Admin sichtbar: ";
								if ($unt->person->email != "") $name .= "E-Mail: " . CHtml::encode($unt->person->email);
								if ($unt->person->email != "" && $unt->person->telefon != "") $name .= ", ";
								if ($unt->person->telefon != "") $name .= "Telefon: " . CHtml::encode($unt->person->telefon);
								$name .= ")</small>";
							}
							$x[] = $name;
						}
						echo implode(", ", $x);
						?></td>
				</tr>
				<tr>
					<th>Status:</th>
					<td><?php
                        if ($antrag->status == IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT) {
							if ($antrag->veranstaltung->veranstaltungsreihe->subdomain == "wiesbaden") {
								echo '<span class="ungeprueft">Eingereicht</span>';
							} else {
								echo '<span class="ungeprueft">' . CHtml::encode(IAntrag::$STATI[$antrag->status]) . '</span>';
							}
                        } elseif ($antrag->status == IAntrag::$STATUS_EINGEREICHT_GEPRUEFT && $antrag->veranstaltung->getEinstellungen()->freischaltung_antraege_anzeigen) {
                            echo '<span class="geprueft">Bestätigt</span>';
                        } else {
                            echo CHtml::encode(IAntrag::$STATI[$antrag->status]);
                        }
						if (trim($antrag->status_string) != "") echo " <small>(" . CHtml::encode($antrag->status_string) . ")</string>";
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
				<?php
				if ($antrag->veranstaltung->isAdminCurUser() && count($antrag->veranstaltung->tags) > 0) {
					?>
					<tr>
						<th><?
                            if (count($antrag->veranstaltung->tags) > 0 && $antrag->veranstaltung->tags[0]->istTagesordnungspunkt()) {
                                echo "Tagesordnungspunkt";
                            } else {
                                echo (count($antrag->tags) == 1 ? "Themenbereich" : "Themenbereiche");
                            }
                            ?>:</th>
						<td>
							<?php
							$tags         = array();
							$used_tag_ids = array();
							foreach ($antrag->tags as $tag) {
								$used_tag_ids[] = $tag->id;
								$dellink        = $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id, AntiXSS::createToken("del_tag") => $tag->id));
								$str            = CHtml::encode($tag->name) . ' <a href="' . CHtml::encode($dellink) . '" style="color: red; text-style: italic; font-size: 0.7em;">del</a>';
								$tags[]         = $str;
							}
							echo implode(", ", $tags);
							?>&nbsp; &nbsp; <a href="#" class="tag_adder_holder" style="color: green;">Neu</a>

							<form method="POST" style="display: none;" id="tag_adder_form">
								<select name="tag_id" size="1" title="Schlagwort aussuchen">
									<option>-</option>
									<?php
									foreach ($antrag->veranstaltung->tags as $tag) if (!in_array($tag->id, $used_tag_ids)) echo '<option value="' . IntVal($tag->id) . '">' . CHtml::encode($tag->name) . '</option>';
									?>
								</select>
								<button class="btn btn-primary" type="submit" name="<?php echo CHtml::encode(AntiXSS::createToken("add_tag")) ?>"
										style="margin: 0; margin-top: -10px;">Hinzufügen
								</button>
							</form>
							<script>
								$(function () {
									$(".tag_adder_holder").click(function (ev) {
										ev.preventDefault();
										$(this).hide();
										$("#tag_adder_form").show();
									})
								})
							</script>
						</td>
					</tr>
				<?php
				} elseif (count($antrag->tags) > 0) {
					?>
					<tr>
						<th><?php
                            if (count($antrag->veranstaltung->tags) > 0 && $antrag->veranstaltung->tags[0]->istTagesordnungspunkt()) {
                                echo "Tagesordnungspunkt";
                            } else {
                                echo (count($antrag->tags) == 1 ? "Themenbereich" : "Themenbereiche");
                            }
                            ?>:</th>
						<td>
							<?php
							$tags = array();
							foreach ($antrag->tags as $tag) $tags[] = $tag->name;
							echo CHtml::encode(implode(", ", $tags));
							?>
						</td>
					</tr>
				<?php
				}
				if ($antrag->abgeleitetVon) {
					?>
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

<?
$text2name = veranstaltungsspezifisch_text2_name($antrag->veranstaltung, $antrag->typ);
if ($text2name) {
    $classes = "";
    if (!in_array(AntragKommentar::ABSATZ_TEXT2, $kommentare_offen)) $classes .= " kommentare_closed_absatz";
    ?>

    <div class="begruendungs_text_holder row-absatz<?=$classes?>">
        <h3><?=CHtml::encode($text2name)?></h3>

        <?
        if ($antrag->veranstaltung->getEinstellungen()->begruendung_kommentierbar) {
            $anzahl = count($spezielle_kommentare[AntragKommentar::ABSATZ_TEXT2]);
            ?>
            <ul class="lesezeichen">
                <?php
                if ($anzahl > 0 || $antrag->veranstaltung->darfEroeffnenKommentar()) {
                    ?>
                    <li class='kommentare'>
                        <a href='#' class='shower'><?php echo $anzahl; ?></a>
                        <a href='#' class='hider'><?php echo $anzahl; ?></a>
                    </li>
                <?
                }
                ?>
            </ul>
        <?
        }
        ?>

        <div class="textholder consolidated content">
            <?php
            echo HtmlBBcodeUtils::bbcode2html($antrag->text2);
            ?>
        </div>

        <?
        if ($antrag->veranstaltung->getEinstellungen()->begruendung_kommentierbar) {
            $this->renderPartial("anzeige_kommentare", array(
                "antrag"           => $antrag,
                "absatz_nr"        => AntragKommentar::ABSATZ_TEXT2,
                "komm_del_link"    => $komm_del_link,
                "js_protection"    => $js_protection,
                "hiddens"          => $hiddens,
                "kommentar_person" => $kommentar_person,
                "kommentare"       => $spezielle_kommentare[AntragKommentar::ABSATZ_TEXT2],
            ));
        }
        ?>
    </div>
<?
}

?>

<div
	class="antrags_text_holder<?php if ($antrag->veranstaltung->getEinstellungen()->zeilenlaenge > 80) echo " kleine_schrift"; ?>">
	<h3><?php
        $text1name = veranstaltungsspezifisch_text1_name($antrag->veranstaltung, $antrag->typ);
        if ($text1name) {
            echo CHtml::encode($text1name);
        } else {
            echo $sprache->get("Antragstext");
        }
        ?></h3>

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
					<li class='kommentare'>
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
						echo DiffUtils::renderBBCodeDiff2HTML($abs->str_bbcode, $par[$i], false, $this->veranstaltung->getEinstellungen()->zeilenlaenge);
						?>
					</div>
				<?php
				}
			}

			$this->renderPartial("anzeige_kommentare", array(
				"antrag"           => $antrag,
				"absatz_nr"        => $abs->absatz_nr,
				"komm_del_link"    => $komm_del_link,
				"js_protection"    => $js_protection,
				"hiddens"          => $hiddens,
				"kommentar_person" => $kommentar_person,
				"kommentare"       => $abs->kommentare,
			));
			?>

		</div>
	<?php
	}
	?>
</div>

<?php

if (trim($antrag->begruendung) != "") {
    $classes = "";
    if (!in_array(AntragKommentar::ABSATZ_BEGRUENDUNG, $kommentare_offen)) $classes .= " kommentare_closed_absatz";
    ?>

	<div class="begruendungs_text_holder row-absatz<?=$classes?>">
        <h3><?php
            $bname = veranstaltungsspezifisch_begruendung_name($antrag->veranstaltung, $antrag->typ);
            if ($bname) {
                echo CHtml::encode($bname);
            } else {
                echo "Begründung";
            }
            ?></h3>

        <?
        if ($antrag->veranstaltung->getEinstellungen()->begruendung_kommentierbar) {
            $anzahl = count($spezielle_kommentare[AntragKommentar::ABSATZ_BEGRUENDUNG]);
            ?>
            <ul class="lesezeichen">
                <?php
                if ($anzahl > 0 || $antrag->veranstaltung->darfEroeffnenKommentar()) {
                    ?>
                    <li class='kommentare'>
                        <a href='#' class='shower'><?php echo $anzahl; ?></a>
                        <a href='#' class='hider'><?php echo $anzahl; ?></a>
                    </li>
                <?
                }
                ?>
            </ul>
        <?
        }
        ?>

		<div class="textholder consolidated content">
			<?php
			if ($antrag->begruendung_html) echo $antrag->begruendung;
			else echo HtmlBBcodeUtils::bbcode2html($antrag->begruendung);
			?>
		</div>
        <?
        if ($antrag->veranstaltung->getEinstellungen()->begruendung_kommentierbar) {
            $this->renderPartial("anzeige_kommentare", array(
                "antrag"           => $antrag,
                "absatz_nr"        => AntragKommentar::ABSATZ_BEGRUENDUNG,
                "komm_del_link"    => $komm_del_link,
                "js_protection"    => $js_protection,
                "hiddens"          => $hiddens,
                "kommentar_person" => $kommentar_person,
                "kommentare"       => $spezielle_kommentare[AntragKommentar::ABSATZ_BEGRUENDUNG],
            ));
        }
        ?>
	</div>

<? } ?>

<?php



$curr_user_id                 = (Yii::app()->user->isGuest ? 0 : Yii::app()->user->getState("person_id"));
$unterstuetzerInnen           = $antrag->getUnterstuetzerInnen();
$zustimmung_von               = $antrag->getZustimmungen();
$ablehnung_von                = $antrag->getAblehnungen();
$eintraege                    = (count($zustimmung_von) > 0 || count($ablehnung_von) > 0);
$unterstuetzen_policy         = $antrag->veranstaltung->getPolicyUnterstuetzen();
$kann_unterstuetzen           = $unterstuetzen_policy->checkCurUserHeuristically() && !in_array($antrag->status, Antrag::$STATI_UNSICHTBAR);
$kann_nicht_unterstuetzen_msg = $unterstuetzen_policy->getPermissionDeniedMsg();
foreach ($antrag->antragUnterstuetzerInnen as $unt) if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN && $unt->person->id == $curr_user_id) {
	$kann_unterstuetzen = false;
}

if (count($unterstuetzerInnen) > 0) {
	?>
	<h2>UnterstützerInnen</h2>
	<div class="content">
		<?php
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
		?>
	</div>
<?php
}

if ($eintraege || $kann_unterstuetzen || $kann_nicht_unterstuetzen_msg != "") {
	?>
	<h2>Zustimmung</h2>

	<div class="content">
		<?php


		if (count($zustimmung_von) > 0) {
            if ($antrag->veranstaltung->veranstaltungsreihe->subdomain == 'wiesbaden' && $antrag->veranstaltung->url_verzeichnis == 'phase2') {
                echo "<strong>Unterstützt von:</strong><br>";
            } else {
                echo "<strong>Zustimmung von:</strong><br>";
            }
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


if ($antrag->veranstaltung->getEinstellungen()->antrag_kommentare_ohne_absatz) {
	?>
	<h2>Kommentare</h2>

	<?php
	$this->renderPartial("anzeige_kommentare", array(
		"antrag"           => $antrag,
		"absatz_nr"        => AntragKommentar::ABSATZ_ANTRAG,
		"komm_del_link"    => $komm_del_link,
		"js_protection"    => $js_protection,
		"hiddens"          => $hiddens,
		"kommentar_person" => $kommentar_person,
		"kommentare"       => $spezielle_kommentare[AntragKommentar::ABSATZ_ANTRAG],
	));

}

?>
<script>
	<?php if (!veranstaltungsspezifisch_erzwinge_login($this->veranstaltung)) { ?>
	$('#socialshareprivacy').socialSharePrivacy({
		css_path: "/socialshareprivacy/socialshareprivacy.css"
	});
	<? } ?>

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
