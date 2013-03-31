<div class="form">


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
	<?php echo Yii::t('app', 'Fields with'); ?> <span class="required">*</span> <?php echo Yii::t('app', 'are required'); ?>.
</p>

<?php echo $form->errorSummary($model); ?>

<h2>Allgemeine Einstellungen zur Veranstaltung</h2>
<br>

<fieldset style="margin-top: 10px;">
	<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[wartungs_modus_aktiv]" value="0" <?php if (!$model->getEinstellungen()->wartungs_modus_aktiv) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
	<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[wartungs_modus_aktiv]" value="1" <?php if ($model->getEinstellungen()->wartungs_modus_aktiv) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
	<strong>Wartungsmodus aktiv</strong> <small>(Nur Admins können den Seiteninhalt sehen)</small>
</fieldset>
<br>

<div>
	<?php echo $form->labelEx($model, 'typ'); ?>
	<div style="display: inline-block; width: 450px;">
	<?php echo $form->dropDownList($model, 'typ', Veranstaltung::$TYPEN); ?>
		<br>
		<small>Wirkt sich insb. auf das Wording aus. "Parteitag": "Anträge" und "Änderungsanträge". "Wahlprogramm": "Kapitel" und "Änderungsvorschläge".</small>
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
		<small>Das Datum ist nur bei tatsächlichen Veranstaltungen wie LDKs relevant. Ansonsten einfach auf "0" lassen.</small>
	<?php echo $form->error($model, 'datum_bis'); ?>
</div>
<!-- row -->
<div>
	<?php echo $form->labelEx($model, 'antragsschluss'); ?>
	<div style="display: inline-block; width: 450px;">
	<?php $form->widget('ext.datetimepicker.EDateTimePicker', array(
	'model'     => $model,
	'attribute' => "antragsschluss",
	'options'   => array(
		'dateFormat' => 'yy-mm-dd',
	),
));
	?><br>
		<small>Das Datum hier erscheint auf der Startseite im pinken Kreis. Falls hier nichts steht, verschwindet der Kreis.</small>
		</div>
	<?php echo $form->error($model, 'antragsschluss'); ?>
</div>



<br>
<h2>Feineinstellungen</h2>
<br>

<div>
	<?php echo $form->labelEx($einstellungen, 'antrag_einleitung', array("label" => "PDF-Antrags-Einleitung")); ?>
	<div style="display: inline-block; width: 420px;">
		<?php echo $form->textArea($einstellungen, 'antrag_einleitung'); ?>
		<br>
		<small>Steht im PDF unter "Antrag", also z.B. "an die Landesversammlung in Würzburg"</small>
	</div>
	<?php echo $form->error($einstellungen, 'antrag_einleitung'); ?>
</div>
<br>


	<div>
		<?php echo $form->labelEx($einstellungen, 'logo_url', array("label" => "Logo-URL")); ?>
		<div style="display: inline-block; width: 450px;">
		<?php echo $form->textField($einstellungen, 'logo_url', array('maxlength' => 200)); ?>
			<br>
			<small>Im Regelfall einfach leer lassen. Falls eine URL angegeben wird, wird das angegebene Bild statt dem großen "Antragsgrün"-Logo angezeigt.</small>
		</div>
		<?php echo $form->error($einstellungen, 'logo_url'); ?>
	</div>
	<div>
		<?php echo $form->labelEx($einstellungen, 'fb_logo_url', array("label" => "Facebook-Bild")); ?>
		<div style="display: inline-block; width: 450px;">
		<?php echo $form->textField($einstellungen, 'fb_logo_url', array('maxlength' => 200)); ?>
			<br>
			<small>Dieses Bild erscheint, wenn etwas auf dieser Seite bei Facebook geteilt wird. Vorsicht: nachträglich ändern ist oft heikel, da FB viel zwischenspeichert.</small>
		</div>
		<?php echo $form->error($einstellungen, 'fb_logo_url'); ?>
	</div>
<br>
<div>
	<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[revision_name_verstecken]" value="0" <?php if ($einstellungen->revision_name_verstecken != 1) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
	<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[revision_name_verstecken]" value="1" <?php if ($einstellungen->revision_name_verstecken == 1) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
	<strong>Revisionsname verstecken</strong> <small>(Revisionsnamen wie z.B. "A1", "A2", "Ä1neu" etc.) müssen zwar weiterhin angegeben werden, damit danach sortiert werden kann. Es wird aber nicht mehr angezeigt. Das ist dann praktisch, wenn man eine eigene Nummerierung im Titel der Anträge vornimmt.</small>
</div>
<br>

<div>
	<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[ae_nummerierung_global]" value="0" <?php if ($einstellungen->ae_nummerierung_global != 1) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
	<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[ae_nummerierung_global]" value="1" <?php if ($einstellungen->ae_nummerierung_global == 1) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
	<strong>ÄA-Nummerierung für die ganze Veranstaltung</strong> <small>Bei "Nein" beginnt die Nummerierung der Änderungsanträge bei jedem Antrag bei 1, also "Ä1 zu A1", "Ä1 zu A2", etc. Bei "Ja" gibt es immer nur einen "Ä1", einen "Ä2" etc.</small>
</div>
<br>

<div>
	<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[zeilen_nummerierung_global]" value="0" <?php if ($einstellungen->zeilen_nummerierung_global != 1) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
	<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[zeilen_nummerierung_global]" value="1" <?php if ($einstellungen->zeilen_nummerierung_global == 1) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
	<strong>Zeilennummerierung durchgehend für die ganze Veranstaltung</strong>
</div>
<br>

	<div>
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[ansicht_minimalistisch]" value="0" <?php if ($einstellungen->ansicht_minimalistisch != 1) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[ansicht_minimalistisch]" value="1" <?php if ($einstellungen->ansicht_minimalistisch == 1) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
		<strong>Minimalistische Ansicht</strong> <small>Der Login-Button und der Info-Header über den Anträgen werden versteckt.</small>
	</div>
	<br>

	<h2>Anträge</h2>
<br>
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
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[freischaltung_antraege]" value="0" <?php if (!$einstellungen->freischaltung_antraege) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[freischaltung_antraege]" value="1" <?php if ($einstellungen->freischaltung_antraege) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
		<strong>Freischaltung</strong> von Anträgen
	</fieldset>
	<br>

	<fieldset style="margin-top: 10px;">
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[antrag_neu_braucht_email]" value="0" <?php if (!$model->getEinstellungen()->antrag_neu_braucht_email) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[antrag_neu_braucht_email]" value="1" <?php if ($model->getEinstellungen()->antrag_neu_braucht_email) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
		Angabe der <strong>E-Mail-Adresse</strong> erzwingen <small>(Bei Anträgen und Änderungsanträgen)</small>
	</fieldset>
	<br>

<h2>Änderungsanträge</h2>
<br>
<div>
	<label class="required" for="Veranstaltung_policy_aenderungsantraege" style="padding-top: 10px;">
		Änderungsanträge stellen dürfen:
		<span class="required"></span>
	</label>
	<?php echo $form->dropDownList($model, 'policy_aenderungsantraege', IPolicyAntraege::getAllInstances()); ?>
	<?php echo $form->error($model, 'policy_aenderungsantraege'); ?>
</div>

	<fieldset style="margin-top: 10px;">
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[freischaltung_aenderungsantraege]" value="0" <?php if (!$einstellungen->freischaltung_aenderungsantraege) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[freischaltung_aenderungsantraege]" value="1" <?php if ($einstellungen->freischaltung_aenderungsantraege) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
		<strong>Freischaltung</strong> von Änderungsanträgen
	</fieldset>
	<br>


<h2>Kommentare</h2>
<br>
<div>
	<label class="required" for="Veranstaltung_policy_kommentare" style="padding-top: 10px;">
		Kommentieren dürfen:
		<span class="required"></span>
	</label>
	<?php echo $form->dropDownList($model, 'policy_kommentare', Veranstaltung::$POLICIES); ?>
	<?php echo $form->error($model, 'policy_kommentare'); ?>
</div>

	<fieldset style="margin-top: 10px;">
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[freischaltung_kommentare]" value="0" <?php if (!$einstellungen->freischaltung_kommentare) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[freischaltung_kommentare]" value="1" <?php if ($einstellungen->freischaltung_kommentare) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
		Kommentare müssen durch den Admin <strong>freigeschaltet</strong> werden
	</fieldset>
	<br>

	<fieldset style="margin-top: 10px;">
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[kommentar_neu_braucht_email]" value="0" <?php if (!$einstellungen->kommentar_neu_braucht_email) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[kommentar_neu_braucht_email]" value="1" <?php if ($einstellungen->kommentar_neu_braucht_email) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
		Angabe der <strong>E-Mail-Adresse</strong> erzwingen
	</fieldset>
	<br>

	<fieldset>
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[kommentare_unterstuetzbar]" value="0" <?php if (!$einstellungen->kommentare_unterstuetzbar) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
		<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[kommentare_unterstuetzbar]" value="1" <?php if ($einstellungen->kommentare_unterstuetzbar) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
		Besucher können Kommentare <strong>bewerten</strong>
	</fieldset>
	<br>


<br>
<h2>Benachrichtigungen</h2>
<br>
<div>
	<?php echo $form->labelEx($model, 'admin_email'); ?>
	<?php echo $form->textField($model, 'admin_email', array('maxlength' => 150)); ?>
	<?php echo $form->error($model, 'admin_email'); ?>
</div>
<div>
	<?php echo $form->labelEx($einstellungen, 'bestaetigungs_emails'); ?>
	<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[bestaetigungs_emails]" value="0" <?php if (!$einstellungen->bestaetigungs_emails) echo "checked"; ?>> Nein</label> &nbsp; &nbsp;
	<label style="display: inline;"><input type="radio" name="VeranstaltungsEinstellungen[bestaetigungs_emails]" value="1" <?php if ($einstellungen->bestaetigungs_emails) echo "checked"; ?>> Ja</label> &nbsp; &nbsp;
</div>



<div class="saveholder">
	<?php
	echo CHtml::submitButton(Yii::t('app', 'Save'), array("class" => "btn btn-primary"));
	$this->endWidget();
	?>
</div>
</div><!-- form -->