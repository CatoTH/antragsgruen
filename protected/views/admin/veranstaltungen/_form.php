<?php
/**
 * @var $this VeranstaltungenController
 * @var CActiveForm $form
 * @var $model Veranstaltung
 * @var bool $superadmin
 */

$form = $this->beginWidget('CActiveForm', array(
	'id'                   => 'veranstaltung-form',
	'enableAjaxValidation' => true,
));
$einstellungen = $model->getEinstellungen();

?>

<p class="note">
	<?php echo Yii::t('app', 'Fields with'); ?> <span
		class="required">*</span> <?php echo Yii::t('app', 'are required'); ?>.
</p>

<?php echo $form->errorSummary($model); ?>

<h3>Allgemeine Einstellungen zur Veranstaltung</h3>
<div class="content ">

	<fieldset style="margin-top: 10px;">
		<label style="display: inline;"><input type="checkbox" name="VeranstaltungsEinstellungen[wartungs_modus_aktiv]"
		                                       value="1" <?php if ($model->getEinstellungen()->wartungs_modus_aktiv) echo "checked"; ?>>
			<strong>Wartungsmodus aktiv</strong>
			<small>(Nur Admins können den Seiteninhalt sehen)</small>
		</label>
	</fieldset>
	<br>

	<div>
		<?php echo $form->labelEx($model, 'typ'); ?>
		<div style="display: inline-block; width: 440px;">
			<?php echo $form->dropDownList($model, 'typ', Veranstaltung::$TYPEN); ?>
			<br>
			<small>Wirkt sich insb. auf das Wording aus. "Parteitag": "Anträge" und "Änderungsanträge". "Wahlprogramm":
				"Kapitel" und "Änderungsvorschläge".
			</small>
		</div>
		<?php echo $form->error($model, 'typ'); ?>
	</div>
	<div>
		<?php echo $form->labelEx($model, 'url_verzeichnis'); ?>
		<?php echo $form->textField($model, 'url_verzeichnis', array('maxlength' => 45)); ?>
		<?php echo $form->error($model, 'url_verzeichnis'); ?>
	</div>
	<div>
		<?php echo $form->labelEx($model, 'name'); ?>
		<?php echo $form->textField($model, 'name', array('maxlength' => 200)); ?>
		<?php echo $form->error($model, 'name'); ?>
	</div>
	<div>
		<?php echo $form->labelEx($model, 'name_kurz'); ?>
		<?php echo $form->textField($model, 'name_kurz', array('maxlength' => 45)); ?>
		<?php echo $form->error($model, 'name_kurz'); ?>
	</div>

	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'datum_von'); ?>
		<?php $form->widget('zii.widgets.jui.CJuiDatePicker', array(
			'model'     => $model,
			'attribute' => 'datum_von',
			'value'     => $model->datum_von,
			'options'   => array(
				'showButtonPanel' => true,
				'changeYear'      => true,
				'dateFormat'      => 'yy-mm-dd',
			),
		));
		?>
		<?php echo $form->error($model, 'datum_von'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'datum_bis'); ?>
		<div style="display: inline-block; width: 420px;">
			<?php $form->widget('zii.widgets.jui.CJuiDatePicker', array(
				'model'     => $model,
				'attribute' => 'datum_bis',
				'value'     => $model->datum_bis,
				'options'   => array(
					'showButtonPanel' => true,
					'changeYear'      => true,
					'dateFormat'      => 'yy-mm-dd',
				),
			));
			?>
			<br>
			<small>Das Datum ist nur bei tatsächlichen Veranstaltungen wie LDKs relevant. Ansonsten einfach auf "0"
				lassen.
			</small>
			<?php echo $form->error($model, 'datum_bis'); ?>
		</div>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'antragsschluss'); ?>
		<div style="display: inline-block; width: 440px;">
			<?php $form->widget('ext.datetimepicker.EDateTimePicker', array(
				'model'     => $model,
				'attribute' => "antragsschluss",
				'options'   => array(
					'dateFormat' => 'yy-mm-dd',
				),
			));
			?><br>
			<small>Das Datum hier erscheint auf der Startseite im pinken Kreis. Falls hier nichts steht, verschwindet
				der Kreis.
			</small>
		</div>
		<?php echo $form->error($model, 'antragsschluss'); ?>
	</div>
</div>

<br>

<h3>Feineinstellungen</h3>

<div class="content">
	<div>
		<label style="display: inline;"><input type="checkbox" name="VeranstaltungsEinstellungen[kann_pdf]"
		                                       value="1" <?php if ($einstellungen->kann_pdf) echo "checked"; ?>>
			<strong>Anträge etc. als PDF anbieten</strong></label> &nbsp; &nbsp;
	</div>
	<br>

	<div>
		<?php echo $form->labelEx($einstellungen, 'antrag_einleitung', array("label" => "PDF-Antrags-Einleitung")); ?>
		<div class="std_content_col">
			<?php echo $form->textArea($einstellungen, 'antrag_einleitung'); ?>
			<br>
			<small>Steht im PDF unter "Antrag", also z.B. "an die LDK in Würzburg"</small>
		</div>
		<?php echo $form->error($einstellungen, 'antrag_einleitung'); ?>
	</div>
	<br>


	<div>
		<?php echo $form->labelEx($einstellungen, 'logo_url', array("label" => "Logo-URL")); ?>
		<div class="std_content_col">
			<?php echo $form->textField($einstellungen, 'logo_url', array('maxlength' => 200)); ?>
			<br>
			<small>Im Regelfall einfach leer lassen. Falls eine URL angegeben wird, wird das angegebene Bild statt dem
				großen "Antragsgrün"-Logo angezeigt.
			</small>
		</div>
		<?php echo $form->error($einstellungen, 'logo_url'); ?>
	</div>
	<div>
		<?php echo $form->labelEx($einstellungen, 'fb_logo_url', array("label" => "Facebook-Bild")); ?>
		<div class="std_content_col">
			<?php echo $form->textField($einstellungen, 'fb_logo_url', array('maxlength' => 200)); ?>
			<br>
			<small>Dieses Bild erscheint, wenn etwas auf dieser Seite bei Facebook geteilt wird. Vorsicht: nachträglich
				ändern ist oft heikel, da FB viel zwischenspeichert.
			</small>
		</div>
		<?php echo $form->error($einstellungen, 'fb_logo_url'); ?>
	</div>
	<br>

	<div>
		<label for="VeranstaltungsEinstellungen_zeilenlaenge">Maximale Zeilenlänge</label>

		<div class="std_content_col">
			<input id="VeranstaltungsEinstellungen_zeilenlaenge" name="VeranstaltungsEinstellungen[zeilenlaenge]"
			       type="number" value="<?= $einstellungen->zeilenlaenge ?>"> <br>
			<small>NICHT ändern, nachdem Anträge eingereicht wurden, weil sich dann die Zeilennummern ändern!</small>
		</div>
	</div>

	<div class="antragstypen_row">
		<label>Auswählbare Antragstypen</label>
		<div class="std_content_col"><?
			foreach (Antrag::$TYPEN as $id => $name) {
				echo '<label><input name="antrags_typen_aktiviert[]" value="' . $id . '" type="checkbox" ';
				if (!in_array($id, $einstellungen->antrags_typen_deaktiviert)) echo ' checked';
				echo '> ' . CHtml::encode($name) . '</label>';
			}
		?></div>
	</div>
	<br>

	<div>
		<label style="display: inline;"><input type="checkbox"
		                                       name="VeranstaltungsEinstellungen[revision_name_verstecken]"
		                                       value="1" <?php if ($einstellungen->revision_name_verstecken == 1) echo "checked"; ?>>
			<strong>Antragskürzel verstecken</strong>
			<small>(Antragskürzel wie z.B. "A1", "A2", "Ä1neu" etc.) müssen zwar weiterhin angegeben werden, damit
				danach sortiert werden kann. Es wird aber nicht mehr angezeigt. Das ist dann praktisch, wenn man eine
				eigene Nummerierung im Titel der Anträge vornimmt.
			</small>
		</label> &nbsp; &nbsp;
	</div>
	<br>

	<div>
		<label style="display: inline;"><input type="checkbox"
		                                       name="VeranstaltungsEinstellungen[zeilen_nummerierung_global]"
		                                       value="1" <?php if ($einstellungen->zeilen_nummerierung_global == 1) echo "checked"; ?>>
			<strong>Zeilennummerierung durchgehend für die ganze Veranstaltung</strong></label> &nbsp; &nbsp;
	</div>
	<br>

	<div>
		<label style="display: inline;"><input type="checkbox" name="VeranstaltungsEinstellungen[titel_eigene_zeile]"
		                                       value="1" <?php if ($einstellungen->titel_eigene_zeile == 1) echo "checked"; ?>>
			<strong>Die Überschrift bekommt eine eigene Zeilennummer</strong></label> &nbsp; &nbsp;
	</div>
	<br>

	<div>
		<label style="display: inline;"><input type="checkbox"
		                                       name="VeranstaltungsEinstellungen[ansicht_minimalistisch]"
		                                       value="1" <?php if ($einstellungen->ansicht_minimalistisch == 1) echo "checked"; ?>>
			<strong>Minimalistische Ansicht</strong>
			<small>Der Login-Button und der Info-Header über den Anträgen werden versteckt.</small>
		</label>
	</div>
</div>


<br>

<h3>Anträge</h3>

<div class="content">
	<!-- row -->
	<div>
		<label class="required" for="Veranstaltung_policy_antraege" style="padding-top: 10px;">
			Anträge stellen dürfen:
			<span class="required"></span>
		</label>
		<?php echo $form->dropDownList($model, 'policy_antraege', IPolicyAntraege::getAllInstances()); ?>
		<?php echo $form->error($model, 'policy_antraege'); ?>
	</div>

	<div>
		<label class="required" for="Veranstaltung_policy_unterstuetzen" style="padding-top: 10px;">
			(Änderungs-)Anträge unterstützen dürfen:
			<span class="required"></span>
		</label>
		<?php echo $form->dropDownList($model, 'policy_unterstuetzen', IPolicyUnterstuetzen::getAllInstances()); ?>
		<?php echo $form->error($model, 'policy_unterstuetzen'); ?>
	</div>
	<br>
	<fieldset style="margin-top: 10px;">
		<label style="display: inline;"><input type="checkbox"
		                                       name="VeranstaltungsEinstellungen[freischaltung_antraege]"
		                                       value="1" <?php if ($einstellungen->freischaltung_antraege) echo "checked"; ?>>
			<strong>Freischaltung</strong> von Anträgen</label> &nbsp; &nbsp;
	</fieldset>
	<br>

	<fieldset style="margin-top: 10px;">
		<label style="display: inline;"><input type="checkbox"
		                                       name="VeranstaltungsEinstellungen[initiatorInnen_duerfen_aendern]"
		                                       value="1" <?php if ($einstellungen->initiatorInnen_duerfen_aendern) echo "checked"; ?>>
			AntragstellerInnen dürfen Anträge <strong>nachträglich ändern</strong>.</label> &nbsp; &nbsp;
	</fieldset>
	<br>

	<fieldset style="margin-top: 10px;">
		<label style="display: inline;"><input type="checkbox"
		                                       name="VeranstaltungsEinstellungen[antrag_neu_braucht_email]"
		                                       value="1" <?php if ($model->getEinstellungen()->antrag_neu_braucht_email) echo "checked"; ?>>
			Angabe der <strong>E-Mail-Adresse</strong> erzwingen
			<small>(Bei Anträgen und Änderungsanträgen)</small>
		</label>
	</fieldset>
	<br>
</div>


<h3>Änderungsanträge</h3>

<div class="content">
	<div>
		<label class="required" for="Veranstaltung_policy_aenderungsantraege" style="padding-top: 10px;">
			Änderungsanträge stellen dürfen:
			<span class="required"></span>
		</label>
		<?php echo $form->dropDownList($model, 'policy_aenderungsantraege', IPolicyAntraege::getAllInstances()); ?>
		<?php echo $form->error($model, 'policy_aenderungsantraege'); ?>
	</div>

	<fieldset style="margin-top: 10px;">
		<label style="display: inline;"><input type="checkbox"
		                                       name="VeranstaltungsEinstellungen[freischaltung_aenderungsantraege]"
		                                       value="1" <?php if ($einstellungen->freischaltung_aenderungsantraege) echo "checked"; ?>>
			<strong>Freischaltung</strong> von Änderungsanträgen</label> &nbsp; &nbsp;
	</fieldset>
	<br>

	<div>
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[ae_nummerierung]"
		                                       value="0" <?php if ($einstellungen->ae_nummerierung_global != 1 && $einstellungen->ae_nummerierung_nach_zeile != 1) echo "checked"; ?>>
			<strong>ÄA-Nummerierung separat pro Antrag</strong>
			<small>"Ä1 zu A1", Ä2 zu A1", "Ä1 zu A2", usw.</small>
		</label><br>
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[ae_nummerierung]"
		                                       value="1" <?php if ($einstellungen->ae_nummerierung_global == 1) echo "checked"; ?>>
			<strong>ÄA-Nummerierung für die ganze Veranstaltung</strong>
			<small>"Ä1", "Ä2", "Ä3" usw.</small>
		</label><br>
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[ae_nummerierung]"
		                                       value="2" <?php if ($einstellungen->ae_nummerierung_nach_zeile == 1) echo "checked"; ?>>
			<strong>ÄA-Nummerierung anhand der Bezugszeile</strong>
			<small>"A1-Ä15-1", "A1-Ä15-2", "A2-Ä15-1" usw. Wenn diese Nummerierung gewählt wird, kann sich jeder
				Änderungsantrag nur auf einen einzigen Absatz beziehen.
			</small>
		</label>
	</div>
	<br>

</div>

<h3>Kommentare</h3>

<div class="content">
	<div>
		<label class="required" for="Veranstaltung_policy_kommentare" style="padding-top: 10px;">
			Kommentieren dürfen:
			<span class="required"></span>
		</label>
		<?php echo $form->dropDownList($model, 'policy_kommentare', Veranstaltung::$POLICIES); ?>
		<?php echo $form->error($model, 'policy_kommentare'); ?>
	</div>
	<br>

	<fieldset style="margin-top: 10px;">
		<label style="display: inline;"><input type="checkbox"
		                                       name="VeranstaltungsEinstellungen[freischaltung_kommentare]"
		                                       value="1" <?php if ($einstellungen->freischaltung_kommentare) echo "checked"; ?>>
			Kommentare müssen durch den Admin <strong>freigeschaltet</strong> werden</label>
	</fieldset>
	<br>

	<fieldset style="margin-top: 10px;">
		<label style="display: inline;"><input type="checkbox"
		                                       name="VeranstaltungsEinstellungen[kommentar_neu_braucht_email]"
		                                       value="1" <?php if ($einstellungen->kommentar_neu_braucht_email) echo "checked"; ?>>
			Angabe der <strong>E-Mail-Adresse</strong> erzwingen</label>
	</fieldset>
	<br>

	<fieldset style="margin-top: 10px;">
		<label style="display: inline;"><input type="checkbox"
		                                       name="VeranstaltungsEinstellungen[kommentare_unterstuetzbar]"
		                                       value="1" <?php if ($einstellungen->kommentare_unterstuetzbar) echo "checked"; ?>>
			Besucher können Kommentare <strong>bewerten</strong></label>
	</fieldset>
	<br>
</div>

<br>

<h3>Benachrichtigungen</h3>

<div class="content">
	<div>
		<?php echo $form->labelEx($model, 'admin_email'); ?>
		<?php echo $form->textField($model, 'admin_email', array('maxlength' => 150)); ?>
		<?php echo $form->error($model, 'admin_email'); ?>
	</div>
	<br>

	<div>
		<label style="display: inline;"><input type="checkbox" name="VeranstaltungsEinstellungen[bestaetigungs_emails]"
		                                       value="1" <?php if ($einstellungen->bestaetigungs_emails) echo "checked"; ?>>
			Bestätigungs-E-Mails an die NutzerInnen schicken</label>
	</div>
</div>


<div class="saveholder">
	<?php
	echo CHtml::submitButton(Yii::t('app', 'Save'), array("class" => "btn btn-primary"));
	?>
</div>
<?php
$this->endWidget();
?>
</div><!-- form -->