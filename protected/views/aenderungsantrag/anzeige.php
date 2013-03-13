<?php

/**
 * @var AenderungsantragController $this
 * @var Aenderungsantrag $aenderungsantrag
 * @var $antragstellerinnen array|Person[]
 * @var $unterstuetzerinnen array|Person[]
 * @var array|Person[] $ablehnung_von
 * @var array|Person[] $zustimmung_von
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

$this->breadcrumbs = array(
	CHtml::encode($aenderungsantrag->antrag->veranstaltung0->name_kurz) => $this->createUrl("site/veranstaltung"),
	$sprache->get("Antrag")                                                            => $this->createUrl("antrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag->id)),
	$sprache->get("Änderungsantrag")
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");
$this->pageTitle = $aenderungsantrag->revision_name . " zu: " . $aenderungsantrag->antrag->nameMitRev();

$html = '<ul class="funktionen">';
//$html .= '<li class="unterstuetzen"><a href="#">Änderungsantrag unterstützen</a></li>';
if ($admin_edit) $html .= '<li class="admin_edit">' . CHtml::link("Admin: bearbeiten", $admin_edit) . '</li>';
$html .= '<li class="download">' . CHtml::link($sprache->get("PDF-Version herunterladen"), $this->createUrl("aenderungsantrag/pdf", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id))) . '</li>';
$html .= '<li class="download">' . CHtml::link($sprache->get("PDF: Kompakt"), $this->createUrl("aenderungsantrag/pdfDiff", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id))) . '</li>';
if ($edit_link) $html .= '<li class="edit">' . CHtml::link("Änderungsantrag bearbeiten", $this->createUrl("aenderungsantrag/bearbeiten", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id))) . '</li>';
$html .= '<li class="zurueck">' . CHtml::link("Zurück zum Anfang", $this->createUrl("antrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag_id))) . '</li>
</ul>';

$this->menus_html[] = $html;


$rows = 10;
?>
<h1 class="well">Änderungsantrag</h1>

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

        <table class="antragsdaten">
            <tr>
                <th>Veranstaltung:</th>
                <td><?php
					echo CHtml::link($aenderungsantrag->antrag->veranstaltung0->name, $this->createUrl("site/veranstaltung"));
					?></td>
            </tr>
            <tr>
                <th>Ursprungsantrag:</th>
                <td><?php
					echo CHtml::link($aenderungsantrag->antrag->name, $this->createUrl("antrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag->id)));
					?></td>
            </tr>
            <tr>
                <th><?php echo (count($antragstellerinnen) > 1 ? "AntragsstellerInnen": "AntragsstellerIn"); ?>:</th>
                <td><?php
					$x = array();
					foreach ($antragstellerinnen as $a) {
						$x[] = CHtml::encode($a->name);
					}
					echo implode(", ", $x);
					?></td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><?php
					echo CHtml::encode(IAntrag::$STATI[$aenderungsantrag->status]);
					?></td>
            </tr>
			<?php if ($aenderungsantrag->datum_beschluss != "") { ?>
            <tr>
                <th>Beschlossen am:</th>
                <td><?php
					echo HtmlBBcodeUtils::formatMysqlDate($aenderungsantrag->datum_beschluss);
					?></td>
            </tr>
			<?php } ?>
            <tr>
                <th>Eingereicht:</th>
                <td><?php
					echo HtmlBBcodeUtils::formatMysqlDateTime($aenderungsantrag->datum_einreichung);
					?></td>
            </tr>
        </table>
	<?php
	$this->widget('bootstrap.widgets.TbAlert', array(
        	'block'=> true,
        	'fade' => true,
        ));
	?>
		<!--
	    <div class="hidden-desktop">
		    <div style="text-align: center; padding-top: 25px;">
			    <button class="btn" type="button" style="color: black;"><i class="icon-pdf"></i> PDF-Version</button>
		    </div>
	    </div>
		-->
    </div>
</div>
<br>


<div class="antrags_text_holder well">
<h3>Änderungsantragstext</h3>

<div class="textholder consolidated antrags_text_holder_nummern">
	<?php
	$dummy_komm = new AenderungsantragKommentar();

	$absae = $aenderungsantrag->getAntragstextParagraphs();

	foreach ($absae as $i=> $abs) {
		/** @var AntragAbsatz $abs */

		$kommoffenclass = (!in_array($i, $kommentare_offen) ? "kommentare_closed_absatz" : "");

		?>
        <div class='row-fluid row-absatz <?php echo $kommoffenclass; ?>' data-absatznr='<?php echo $i; ?>'>

            <div class="textabschnitt">
                <div class="absatz_text orig antragabsatz_holder antrags_text_holder_nummern">
					<?php echo $abs->str_html; ?>

                </div>

				<?php if (count($abs->kommentare) > 0 || $aenderungsantrag->antrag->veranstaltung0->darfEroeffnenKommentar()) { ?>
                <div class='kommentare'>
                    <a href='#' class='shower'><?php echo count($abs->kommentare); ?></a>
                    <a href='#' class='hider'><?php echo count($abs->kommentare); ?></a>
                </div>
				<?php } ?>
            </div>
			<?php

			/** @var AenderungsantragKommentar $komm */
			foreach ($abs->kommentare as $komm) {
				$komm_link = $this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id));
				?>
                <div class="kommentarform well" id="komm<?=$komm->id?>">
                    <div class="datum"><?php echo HtmlBBcodeUtils::formatMysqlDateTime($komm->datum)?></div>
                    <h3>Kommentar von <?php echo
						CHtml::encode($komm->verfasser->name);
						if ($komm->status == IKommentar::$STATUS_NICHT_FREI) echo " <em>(noch nicht freigeschaltet)</em>";
						?></h3>
					<?php
					echo nl2br(CHtml::encode($komm->text));
					if (!is_null($komm_del_link) && $komm->kannLoeschen(Yii::app()->user)) echo "<div class='del_link'><a href='" . CHtml::encode(str_replace(rawurlencode("#komm_id#"), $komm->id, $komm_del_link)) . "'>x</a></div>";
					if ($komm->status == IKommentar::$STATUS_NICHT_FREI && $aenderungsantrag->antrag->veranstaltung0->isAdminCurUser()) {
						$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
							'type'        => 'inline',
							'htmlOptions' => array('class' => ''),
							'action' => $komm_link,
						));
						echo '<div style="display: inline-block; width: 49%; text-align: center;">';
						$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'success', 'label' => 'Freischalten', 'icon' => 'icon-thumbs-up', 'htmlOptions' => array('name' => AntiXSS::createToken('komm_freischalten'))));
						echo '</div><div style="display: inline-block; width: 49%; text-align: center;">';
						$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'danger', 'label' => 'Löschen', 'icon' => 'icon-thumbs-down', 'htmlOptions' => array('name' => AntiXSS::createToken('komm_nicht_freischalten'))));
						echo '</div>';
						$this->endWidget();
					}
					?>
					<div class="kommentarlink"><?php echo CHtml::link("Kommentar verlinken", $komm_link); ?></div>
                </div>
				<?php
			}

			if ($aenderungsantrag->antrag->veranstaltung0->darfEroeffnenKommentar()) {
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
					foreach ($hiddens as $name=> $value) {
						echo '<input type="hidden" name="' . CHtml::encode($name) . '" value="' . CHtml::encode($value) . '">';
					}
					echo '<input type="hidden" name="absatz_nr" value="' . $abs->absatz_nr . '">';

					echo $form->textFieldRow($kommentar_person, 'name', array("id" => "Person_name_" . $i, 'labelOptions'=> array("for" => "Person_name_" . $i, 'label'=> 'Name')));
					echo $form->textFieldRow($kommentar_person, 'email', array("id" => "Person_email_" . $i, 'labelOptions'=> array("for" => "Person_email_" . $i, 'label'=> 'E-Mail')));
					?>
                    <div class="control-group "><label class="control-label" for="AenderungsantragKommentar_text_<?=$i?>">Kommentar:</label>

                        <div class="controls">
							<?php echo $form->textArea($dummy_komm, "text", array("id" => "AenderungsantragKommentar_text_" . $i)); ?>
                        </div>
                    </div>

                </fieldset>

                <div>
					<?php
					$this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'type'=> 'primary', 'icon'=> 'ok white', 'label'=> 'Kommentar abschicken'));
					?>
                </div>
				<?php
				$this->endWidget();
			}
			?>

        </div>
		<?php
	}
	//echo HtmlBBcodeUtils::bbcode2html($aenderungsantrag->aenderung_text);
	?>
</div>
<div style="text-align: center;" id="antrags_diff_opener">
	<a href="#" onClick="$('#antrags_diff_holder').show(); $('#antrags_diff_opener').hide(); $('#antrags_diff_closer').show(); return false;"><i class="icon-down-open"></i> Antragstext mit Änderungen anzeigen</a>
</div>
<div style="text-align: center; display: none;" id="antrags_diff_closer">
	<a href="#" onClick="$('#antrags_diff_holder').hide(); $('#antrags_diff_opener').show(); $('#antrags_diff_closer').hide(); return false;"><i class="icon-up-open"></i> Antragstext mit Änderungen anzeigen</a>
</div>
<div id="antrags_diff_holder" class="content" style="display: none;">
	<?php
	$abs_alt = $aenderungsantrag->antrag->getParagraphs();
	$abs_neu = json_decode($aenderungsantrag->text_neu);

	$letztes_leer = false;
	foreach ($abs_alt as $i=> $abs) {
		if (isset($abs_neu[$i]) && $abs_neu[$i] != "") {
			$letztes_leer = false;
			echo "<div class='row-fluid'>";
			/** @var AntragAbsatz $abs */
			echo DiffUtils::renderBBCodeDiff2HTML($abs->str_bbcode, $abs_neu[$i]);
			echo "</div>\n";
		} else {
			if (!$letztes_leer) {
				$letztes_leer = true;
				echo "<div class='absatz_ueberspringen'>.<br>.<br>.</div>";
			}
		}
	}
	?>
</div>
</div>

<div class="begruendungs_text_holder well">
    <h3>Begründung</h3>

    <div class="textholder consolidated">
		<?php echo HtmlBBcodeUtils::bbcode2html($aenderungsantrag->aenderung_begruendung) ?>
    </div>
</div>

<?php
$eintraege = (count($unterstuetzerinnen) > 0 || count($zustimmung_von) > 0 || count($ablehnung_von) > 0);
$unterstuetzen_policy = $aenderungsantrag->antrag->veranstaltung0->getPolicyUnterstuetzen();
$kann_unterstuetzen = $unterstuetzen_policy->checkCurUserHeuristically();
$kann_nicht_unterstuetzen_msg = $unterstuetzen_policy->getPermissionDeniedMsg();

if ($eintraege || $kann_unterstuetzen || $kann_nicht_unterstuetzen_msg != "") {
?>

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
	if ($kann_unterstuetzen) {
		$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
			'type'       => 'inline',
			'htmlOptions'=> array('class'=> 'well'),
		));
		echo "<div style='text-align: center; margin-bottom: 20px;'>";
		switch ($support_status) {
			case IUnterstuetzer::$ROLLE_INITIATOR:
				break;
			case IUnterstuetzer::$ROLLE_MAG:
				$this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'label'=> 'Zurückziehen', 'icon' => 'icon-remove', 'htmlOptions'=> array('name'=> AntiXSS::createToken('dochnicht'))));
				break;
			case IUnterstuetzer::$ROLLE_MAG_NICHT:
				$this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'label'=> 'Zurückziehen', 'icon' => 'icon-remove', 'htmlOptions'=> array('name'=> AntiXSS::createToken('dochnicht'))));
				break;
			default:
				?>
                    <div style="display: inline-block; width: 49%; text-align: center;">
						<?php
						$this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'type' => 'success', 'label'=> 'Zustimmen', 'icon' => 'icon-thumbs-up', 'htmlOptions'=> array('name'=> AntiXSS::createToken('mag'))));
						?>
                    </div>
                    <div style="display: inline-block; width: 49%; text-align: center;">
						<?php
						$this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'type' => 'danger', 'label'=> 'Ablehnen', 'icon' => 'icon-thumbs-down', 'htmlOptions'=> array('name'=> AntiXSS::createToken('magnicht'))));
						?>
                    </div>
					<?php
		}
		echo "</div>";
		$this->endWidget();
	} else {
		/*
		Yii::app()->user->setFlash('warning', 'Um diesen Änderungsantrag unterstützen oder ablehnen zu können, musst du ' . CHtml::link("dich einzuloggen", $this->createUrl("site/login")) . '.');
		$this->widget('bootstrap.widgets.TbAlert', array(
			'block'=> true,
			'fade' => true,
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
</div>
<?php
}