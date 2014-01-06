<?php

/**
 * @var AntragsgruenController $this
 * @var bool $eingeloggt
 * @var bool $email_angegeben
 * @var bool $email_bestaetigt
 * @var Person $ich
 * @var string $msg_ok
 * @var string $msg_err
 * @var null|VeranstaltungsreihenAbo $aktuelle_einstellungen
 */


$this->pageTitle = Yii::app()->name . ' - Passwort';
$this->breadcrumbs = array(
	"Passwort",
);

?>
<h1>Passwort ändern</h1>

<div class="content">
	<br>

	<?php if ($msg_ok != "") { ?>
		<div class="alert alert-success">
			<?php echo $msg_ok; ?>
		</div>
	<?php
	}
	if ($msg_err != "") {
		?>
		<div class="alert alert-error">
			<?php echo $msg_err; ?>
		</div>
	<? } ?>

	<form method="POST" id="password_form">

		<label>
			<strong>Bisheriges Passwort:</strong><br>
			<input type="password" name="pw_alt" id="pw_alt" value="" style="width: 280px;" required>
		</label>

		<label>
			<strong>Neues Passwort:</strong><br>
			<input type="password" name="pw_neu" id="pw_neu" value="" style="width: 280px;" required>
		</label>

		<label>
			<strong>Neues Passwort:</strong> (Wiederholung)<br>
			<input type="password" name="pw_neu2" id="pw_neu2" value="" style="width: 280px;" required>
		</label>

		<div>
			<button type="submit" name="<?php echo AntiXSS::createToken("speichern"); ?>"
			        class="btn btn-primary" id="savebutton">Speichern
			</button>
		</div>
	</form>

	<script>
		$(function() {
			$("#password_form").on("submit", function(ev) {
				if ($("#pw_neu").val() != $("#pw_neu2").val()) {
					alert("Die beiden Passwörter stimmen nicht überein.");
					ev.preventDefault();
					return;
				}
				if ($.trim($("#pw_neu").val()).length < 5) {
					alert("Das Passwort muss mindestens fünf Zeichen lang sein.");
					ev.preventDefault();
					return;
				}
			});
		})
	</script>
	<br><br>
</div>
