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
		} else {
			?>
			<label>
				<input type="checkbox" name="neuer_account" id="neuer_account_check" <?php if (isset($_REQUEST["neuer_account"])) echo "checked"; ?>>
				Neuen Zugang anlegen
			</label>
			<br>
		<?php } ?>

		<label>
			E-Mail-Adresse / BenutzerInnenname:<br>
			<input class="span3" name="username" id="username" type="text" autofocus required placeholder="E-Mail-Adresse" value="<?php
			if (isset($_REQUEST["username"])) echo CHtml::encode($_REQUEST["username"]);
			?>">
		</label>

		<label>
			Passwort:<br>
			<input type="password" value="" autocomplete="false" name="password" id="password_input" required>
		</label>

		<label id="pwd_confirm" style="display: none;">
			Passwort (Bestätigung):<br>
			<input type="password" value="" autocomplete="false" name="password_confirm">
		</label>

		<label id="reg_name" style="display: none;">
			Dein Name:<br>
			<input type="text" value="<?php if (isset($_REQUEST["name"])) echo CHtml::encode($_REQUEST["name"]); ?>" name="name">
		</label>

		<script>
			$(function () {
				$("#neuer_account_check").change(function () {
					if ($(this).prop("checked")) {
						$("#pwd_confirm").show();
						$("#reg_name").show().find("input").attr("required", "required");
						$("#password_input").attr("placeholder", "Min. 6 Zeichen");
					} else {
						$("#pwd_confirm").hide();
						$("#reg_name").hide().find("input").removeAttr("required");
						$("#password_input").attr("placeholder", "");
					}
				}).trigger("change");
			})
		</script>

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
