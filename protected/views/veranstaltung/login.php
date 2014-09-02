<?php
/**
 * @var VeranstaltungController $this
 * @var OAuthLoginForm $model
 * @var string $msg_err
 */
$this->breadcrumbs = array(
	'Login',
);


if ($msg_err != "") {
	?>
	<h1>Fehler</h1>
	<div class="content">
		<div class="alert alert-error">
			<?php echo $msg_err; ?>
		</div>
	</div>
<?
}

?>

	<h1>Login</h1>



	<h2>Login per BenutzerInnenname / Passwort</h2>
	<div class="content">

		<?php /** @var CActiveForm $form */
		$form = $this->beginWidget('CActiveForm');

		if ($this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_namespaced_accounts) {
			echo '<div class="alert alert-info">';
			echo "<strong>Hinweis:</strong> wenn du berechtigt bist, (Änderungs-)Anträge einzustellen, solltest du die Zugangsdaten per E-Mail erhalten haben.<br>
		Falls du keine bekommen haben solltest, melde dich bitte bei den Organisatoren dieses Parteitags / dieser Programmdiskussion.</div>";
		}
		?>

		<label for="username">E-Mail-Adresse / BenutzerInnenname</label>
		<input class="span3" name="username" id="username" type="text" autofocus>

		<label>Passwort:<br><input type="password" value="" autocomplete="false" name="password"></label>

		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'icon' => 'ok', 'label' => 'Einloggen')); ?>

		<?php $this->endWidget(); ?>

	</div>


	<h2>Wurzelwerk-Login <?php
		if ($this->veranstaltungsreihe && $this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_namespaced_accounts) {
			echo "(Nur Admins)";
		}
		?></h2>
	<div class="content">
		<?php /** @var CActiveForm $form */
		$form = $this->beginWidget('CActiveForm');
		?>
		<label for="OAuthLoginForm_wurzelwerk">WurzelWerk-Account</label>
		<input class="span3" name="OAuthLoginForm[wurzelwerk]" id="OAuthLoginForm_wurzelwerk" type="text"
			   style="margin-bottom: 0; "/><br><a href="https://www.netz.gruene.de/passwordForgotten.form" target="_blank"
												  style="font-size: 0.8em; margin-top: -7px; display: inline-block; margin-bottom: 10px;">Wurzelwerk-Zugangsdaten
			vergessen?</a>
		<span class="help-block error" id="OAuthLoginForm_wurzelwerk_em_" style="display: none"></span>

		<br>

		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'icon' => 'ok', 'label' => 'Einloggen')); ?>

		<div style="border-left: solid 1px #808080; padding-left: 10px; margin-top: 10px;">
			<small><strong>Hinweis:</strong> Hier wirst du auf eine Seite unter "https://service.gruene.de/" umgeleitet, die
				vom Bundesverband betrieben wird.<br>Dort musst du dein Wurzelwerk-BenutzerInnenname/Passwort eingeben und
				bestätigen, dass deine E-Mail-Adresse an Antragsgrün übermittelt wird. Dein Wurzelwerk-Passwort bleibt
				geheim und wird <i>nicht</i> an Antragsgrün übermittelt.
			</small>
		</div>

		<?php $this->endWidget(); ?>

	</div>
<?php
if (!$this->veranstaltungsreihe || !$this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_namespaced_accounts) {
	?>
	<h2>OpenID-Login</h2>
	<div class="content">
		<?php /** @var CActiveForm $form */
		$form = $this->beginWidget('CActiveForm'); ?>

		<label for="OAuthLoginForm_openid_identifier">OpenID-URL</label>
		<input class="span3" name="OAuthLoginForm[openid_identifier]" id="OAuthLoginForm_openid_identifier"
			   type="text"/>
		<span class="help-block error" id="OAuthLoginForm_openid_identifier_em_" style="display: none"></span>

		<br>

		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'icon' => 'ok', 'label' => 'Einloggen')); ?>

		<?php $this->endWidget(); ?>
	</div>
<?
}
