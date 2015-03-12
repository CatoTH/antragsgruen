<?

/**
 * @var AntragController $this
 * @var string $komm_del_link
 * @var int $absatz_nr
 * @var bool $js_protection
 * @var array $hiddens
 * @var Person $kommentar_person
 * @var Antrag $antrag
 * @var AntragKommentar $kommentare
 */

foreach ($kommentare as $komm) {
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
			<div class="kommentarlink"><?php echo CHtml::link("Kommentar verlinken", $komm_link); ?></div>
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
				JavaScript aktiviert sein, oder du musst eingeloggt sein.
			</div>
		<?php
		}
		foreach ($hiddens as $name => $value) {
			echo '<input type="hidden" name="' . CHtml::encode($name) . '" value="' . CHtml::encode($value) . '">';
		}
		echo '<input type="hidden" name="absatz_nr" value="' . $absatz_nr . '">';
		if (!($this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_namespaced_accounts && veranstaltungsspezifisch_erzwinge_login($this->veranstaltung))) {
			?>
			<div class="row">
				<?php echo $form->labelEx($kommentar_person, 'name'); ?>
				<?php echo $form->textField($kommentar_person, 'name') ?>
			</div>
			<div class="row">
				<?php echo $form->labelEx($kommentar_person, 'email'); ?>
				<?php echo $form->emailField($kommentar_person, 'email') ?>
			</div>
		<?php } ?>
		<div class="row">
			<label class="required" style="display: none;">Text</label>
			<textarea name="AntragKommentar[text]" title="Text"></textarea>
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