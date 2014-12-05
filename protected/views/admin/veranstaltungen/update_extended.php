<?php
/**
 * @var VeranstaltungenController $this
 * @var CActiveForm $form
 * @var Veranstaltung $model
 */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl("admin/index"),
	"Veranstaltung"
);
?>

<h1><?php echo Yii::t('app', 'Update') . ': ' . GxHtml::encode($model->label()) . ' ' . GxHtml::encode(GxHtml::valueEx($model)); ?></h1>

<?

$form          = $this->beginWidget('CActiveForm', array(
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

<div class="content">
	<div>
		<label style="display: inline;">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="kann_pdf">
			<input type="checkbox" name="VeranstaltungsEinstellungen[kann_pdf]" value="1" <?php if ($einstellungen->kann_pdf) echo "checked"; ?>>
			<strong>Anträge etc. als PDF anbieten</strong></label> &nbsp; &nbsp;
	</div>
	<br>

	<div>
		<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="logo_url">
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
		<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="fb_logo_url">
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
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="zeilenlaenge">
			<input id="VeranstaltungsEinstellungen_zeilenlaenge" name="VeranstaltungsEinstellungen[zeilenlaenge]"
				   type="number" value="<?= $einstellungen->zeilenlaenge ?>"> <br>
			<small>NICHT ändern, nachdem Anträge eingereicht wurden, weil sich dann die Zeilennummern ändern!</small>
		</div>
	</div>

	<div>
		<label style="display: inline;">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="revision_name_verstecken">
			<input type="checkbox" name="VeranstaltungsEinstellungen[revision_name_verstecken]"
				   value="1" <?php if ($einstellungen->revision_name_verstecken == 1) echo "checked"; ?>>
			<strong>Antragskürzel verstecken</strong><br>
			<small style="margin-left: 20px; display: block;">(Antragskürzel wie z.B. "A1", "A2", "Ä1neu" etc.) müssen zwar weiterhin angegeben werden, damit
				danach sortiert werden kann. Es wird aber nicht mehr angezeigt. Das ist dann praktisch, wenn man eine
				eigene Nummerierung im Titel der Anträge vornimmt.
			</small>
		</label>
	</div>
	<br>


	<div>
		<label style="display: inline;">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="feeds_anzeigen">
			<input type="checkbox" name="VeranstaltungsEinstellungen[feeds_anzeigen]"
				   value="1" <?php if ($einstellungen->feeds_anzeigen) echo "checked"; ?>>
			Feeds in der Sidebar anzeigen
		</label>
	</div>
	<br>

	<div>
		<label style="display: inline;">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="titel_eigene_zeile">
			<input type="checkbox" name="VeranstaltungsEinstellungen[titel_eigene_zeile]" value="1" <?php if ($einstellungen->titel_eigene_zeile == 1) echo "checked"; ?>>
			<strong>Die Überschrift bekommt eine eigene Zeilennummer</strong></label> &nbsp; &nbsp;
	</div>
	<br>

	<div>
		<label style="display: inline;">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="ansicht_minimalistisch">
			<input type="checkbox" name="VeranstaltungsEinstellungen[ansicht_minimalistisch]"
											   value="1" <?php if ($einstellungen->ansicht_minimalistisch == 1) echo "checked"; ?>>
			<strong>Minimalistische Ansicht</strong><br>
			<small style="margin-left: 20px;">Der Login-Button und der Info-Header über den Anträgen werden versteckt.</small>
		</label>
	</div>
	<br>

	<div>
		<label style="display: inline;">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="bdk_startseiten_layout">
			<input type="checkbox" name="VeranstaltungsEinstellungen[bdk_startseiten_layout]"
											   value="1" <?php if ($einstellungen->bdk_startseiten_layout == 1) echo "checked"; ?>>
			<strong>Antragsübersicht der Startseite im BDK-Stil</strong>
		</label>
	</div>
</div>


<br>

<h3>Anträge</h3>

<div class="content">

	<div>
		<label class="required" for="Veranstaltung_policy_unterstuetzen" style="padding-top: 10px;">
			(Änderungs-)Anträge unterstützen dürfen:
			<span class="required"></span>
		</label>
		<?php echo $form->dropDownList($model, 'policy_unterstuetzen', IPolicyUnterstuetzen::getAllInstances()); ?>
		<?php echo $form->error($model, 'policy_unterstuetzen'); ?>
	</div>

	<div>
		<label style="padding-top: 10px;">Mögliche Schlagworte:</label>

		<div style="display: inline-block;">
			<?
			if (count($model->tags) > 0) {
				echo '<ul>';
				foreach ($model->tags as $tag) {
					echo '<li>' . CHtml::encode($tag->name) . ' (' . count($tag->antraege) . ')';
					if (count($tag->antraege) == 0) echo ' <a href="' . CHtml::encode($this->createUrl("admin/veranstaltungen/update_extended",
							array(AntiXSS::createToken("del_tag") => $tag->id))) .
						'" onClick="return confirm(\'Wirklich löschen?\');" style="color: red; font-size: 0.8em;">löschen</a>';
					echo '</li>';
				}
				echo '</ul>';
			} else {
				echo '<em>Keine</em> &nbsp; &nbsp;';
			}
			?>
			<a href="#" class="tag_neu_opener">+ Neues hinzufügen</a>
			<input class="tag_neu_input" name="tag_neu" placeholder="Neues Schlagwort" value="" style="display: none;">
		</div>
		<script>
			$(".tag_neu_opener").click(function (ev) {
				ev.preventDefault();
				$(".tag_neu_input").show().focus();
				$(this).hide();
			});
		</script>
	</div>
	<br>

	<fieldset style="margin-top: 10px;" id="admins_duerfen_aendern">
		<label class="block">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="admins_duerfen_aendern">
			<input type="checkbox" name="VeranstaltungsEinstellungen[admins_duerfen_aendern]" value="1" <?php if ($einstellungen->admins_duerfen_aendern) echo "checked"; ?>>
			Admins dürfen Antrags-Texte <strong>nachträglich ändern</strong>.
		</label>
		<br>
	</fieldset>

	<fieldset style="margin-top: 10px;" id="initiatorInnen_duerfen_aendern">
		<label class="block">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="initiatorInnen_duerfen_aendern">
			<input type="checkbox" name="VeranstaltungsEinstellungen[initiatorInnen_duerfen_aendern]"
				   value="1" <?php if ($einstellungen->initiatorInnen_duerfen_aendern) echo "checked"; ?>>
			AntragstellerInnen dürfen Anträge <strong>nachträglich ändern</strong>.
		</label>
		<br>
	</fieldset>

	<script>
		$(function () {
			var $admins_duerfen = $("#admins_duerfen_aendern").find("input"),
				$initiatorInnen = $("#initiatorInnen_duerfen_aendern");
			$admins_duerfen.change(function () {
				if ($(this).prop("checked")) {
					$initiatorInnen.show();
				} else {
					if (!confirm("Wenn dies deaktiviert wird, wirkt sich das auch auf alle bisherigen Anträge aus und kann für bisherige Anträge nicht rückgängig gemacht werden. Wirklich setzen?")) {
						$(this).prop("checked", true);
					} else {
						$initiatorInnen.hide();
						$initiatorInnen.find("input").prop("checked", false);
					}
				}
			});
			if (!$admins_duerfen.prop("checked")) $initiatorInnen.hide();
		})
	</script>

	<fieldset style="margin-top: 10px;">
		<label class="block">
			<input type="checkbox" name="antrag_neu_nur_namespaced_accounts"
									value="1" <?php if ($model->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_namespaced_accounts) echo "checked"; ?>>
			Login nur von <?php echo CHtml::link("Veranstaltungsreihen-BenutzerInnen", array("/admin/index/namespacedAccounts")); ?> zulassen<br>
			<small style="margin-left: 20px;">(gilt für Anträge und Änderungsanträge der gesamten Veranstaltungs<span style="text-decoration: underline;">reihe</span>)</small>
		</label>
	</fieldset>

	<fieldset style="margin-top: 10px;">
		<label class="block">
			<input type="checkbox" name="antrag_neu_nur_wurzelwerk" value="1"
				<?php if ($model->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_wurzelwerk) echo "checked"; ?>>
			Login nur von Wurzelwerk-NutzerInnen zulassen<br>
			<small style="margin-left: 20px;">(gilt für Anträge und Änderungsanträge der gesamten Veranstaltungs<span style="text-decoration: underline;">reihe</span>)</small>
		</label>
	</fieldset>

	<fieldset style="margin-top: 10px;">
		<label class="block" style="line-height: 30px; vertical-align: middle;">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="antragstext_max_len">
			<input type="checkbox" id="max_len_set" <?php if ($model->getEinstellungen()->antragstext_max_len > 0) echo "checked"; ?>>
			Maximale Länge der Anträge<span class="max_len_holder">:
				<input type="number" name="VeranstaltungsEinstellungen[antragstext_max_len]" value="<?php echo $model->getEinstellungen()->antragstext_max_len; ?>" size="4"> Zeichen
			</span>
		</label>
		<script>
			$(function () {
				$("#max_len_set").change(function () {
					if ($(this).prop("checked")) $(this).parents("fieldset").first().find(".max_len_holder").show();
					else $(this).parents("fieldset").first().find(".max_len_holder").hide();
				}).change();
			});
		</script>
	</fieldset>


	<fieldset style="margin-top: 10px;">
		<label class="block">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="antrag_begruendungen">
			<input type="checkbox" name="VeranstaltungsEinstellungen[antrag_begruendungen]"
				   value="1" <?php if ($model->getEinstellungen()->antrag_begruendungen) echo "checked"; ?>>
			Ein extra Feld für Antragsbegründungen anzeigen
		</label>
	</fieldset>


	<fieldset style="margin-top: 10px;">
		<label class="block">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="antrag_kommentare_ohne_absatz">
			<input type="checkbox" name="VeranstaltungsEinstellungen[antrag_kommentare_ohne_absatz]"
				   value="1" <?php if ($model->getEinstellungen()->antrag_kommentare_ohne_absatz) echo "checked"; ?>>
			Kommentare zum Antrag allgemein zulassen<br>
			<small style="margin-left: 20px;">(Anträge ohne Absatzbezug, erscheinen unterhalb des Antrags)</small>
		</label>
	</fieldset>


	<fieldset style="margin-top: 10px;">
		<label class="block">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="begruendung_in_html">
			<input type="checkbox" name="VeranstaltungsEinstellungen[begruendung_in_html]"
				   value="1" <?php if ($model->getEinstellungen()->begruendung_in_html) echo "checked"; ?>>
			Erweiterte Formatierungen (HTML) in Anträgen und Änderungsanträgen zulassen <span style="color: red; font-size: 10px;">(Beta, noch fehleranfällig)</span>
		</label>
	</fieldset>

</div>


<h3>Kommentare</h3>

<div class="content">

	<fieldset style="margin-top: 10px;">
		<label style="display: inline;">
			<input type="hidden" name="VeranstaltungsEinstellungen[einstellungsfelder][]" value="kommentare_unterstuetzbar">
			<input type="checkbox" name="VeranstaltungsEinstellungen[kommentare_unterstuetzbar]"
											   value="1" <?php if ($einstellungen->kommentare_unterstuetzbar) echo "checked"; ?>>
			Besucher können Kommentare <strong>bewerten</strong></label>
	</fieldset>
	<br>
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