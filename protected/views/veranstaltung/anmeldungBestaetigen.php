<?php
/**
 * @var VeranstaltungController $this
 * @var string $email
 * @var string $errors
 */

?>

<h1>Bestätige deinen Zugang</h1>

<div class="content">
	<?php
	if ($errors != "") echo '<div class="alert alert-error">' . CHtml::encode($errors) . '</div>';
	else echo '<div class="alert alert-info">Dir wurde eben eine E-Mail an die angegebene Adresse geschickt. Bitte bestätige den Empfang dieser E-Mail, indem du den Link darin aufrufst oder hier den Code in der E-Mail eingibst.</div>';
	?>

	<form method="POST" action="<?php echo CHtml::encode($this->createUrl("veranstaltung/anmeldungBestaetigen", array("email" => $email))); ?>">
		<label>
			E-Mail-Adresse / BenutzerInnenname:<br>
			<input type="text" value="<?php echo CHtml::encode($email); ?>" id="username" <?php if ($email != "") echo "disabled"; ?>>
		</label>
		<br>

		<label>
			Bestätigungs-Code:<br>
			<input type="text" name="code" value="">
		</label>
		<br>

		<input type="submit" value="Bestätigen" class="btn btn-primary">
	</form>
</div>
