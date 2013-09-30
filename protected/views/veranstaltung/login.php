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
	<div class="well"><div class="content">
	<div class="alert alert-error">
		<?php echo $msg_err; ?>
	</div>
		</div></div>
<? }

?>

<h1>Login</h1>

<h2>Wurzelwerk-Login</h2>
<div class="well">
	<div class="content">
		<?php /** @var CActiveForm $form */
		$form = $this->beginWidget('CActiveForm', array(
			"htmlOptions" => array(
				"class" => "well well_first",
			),
		));
		?>
		<label for="OAuthLoginForm_wurzelwerk">WurzelWerk-Account</label>
		<input class="span3" name="OAuthLoginForm[wurzelwerk]" id="OAuthLoginForm_wurzelwerk" type="text" style="margin-bottom: 0; "/><br><a href="https://www.netz.gruene.de/passwordForgotten.form" target="_blank" style="font-size: 0.8em; margin-top: -7px; display: inline-block; margin-bottom: 10px;">Wurzelwerk-Zugangsdaten vergessen?</a>
		<span class="help-block error" id="OAuthLoginForm_wurzelwerk_em_" style="display: none"></span>

		<br>

		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'icon' => 'ok', 'label' => 'Einloggen')); ?>

		<?php $this->endWidget(); ?>

	</div>
</div>

<h2>Login per BenutzerInnenname / Passwort</h2>
<div class="well">
	<div class="content">

		<?php /** @var CActiveForm $form */
		$form = $this->beginWidget('CActiveForm', array(
			"htmlOptions" => array(
				"class" => "well well_first",
			),
		)); ?>


		<label for="OAuthLoginForm_wurzelwerk">E-Mail-Adresse / BenutzerInnenname</label>
		<input class="span3" name="OAuthLoginForm[wurzelwerk]" id="OAuthLoginForm_wurzelwerk" type="text"/>
		<span class="help-block error" id="OAuthLoginForm_wurzelwerk_em_" style="display: none"></span>

		<label>Passwort:<br><input type="password" value="" autocomplete="false" name="password"></label>

		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'icon' => 'ok', 'label' => 'Einloggen')); ?>

		<?php $this->endWidget(); ?>

	</div>
</div>

<h2>OpenID-Login</h2>
<div class="well">
	<div class="content">
		<?php /** @var CActiveForm $form */
		$form = $this->beginWidget('CActiveForm', array(
			"htmlOptions" => array(
				"class" => "well well_first",
			),
		)); ?>

		<label for="OAuthLoginForm_openid_identifier">OpenID-URL</label>
		<input class="span3" name="OAuthLoginForm[openid_identifier]" id="OAuthLoginForm_openid_identifier" type="text"/>
		<span class="help-block error" id="OAuthLoginForm_openid_identifier_em_" style="display: none"></span>

		<br>

		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'icon' => 'ok', 'label' => 'Einloggen')); ?>

		<?php $this->endWidget(); ?>
	</div>
</div>