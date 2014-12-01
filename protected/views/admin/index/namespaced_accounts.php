<?php
/**
 * @var IndexController $this
 * @var Person[] $accounts
 * @var string $msg
 */
$this->breadcrumbs = array(
	'Administration' => $this->createUrl("admin/index"),
	'BenutzerInnen',
);

$pre_emails = "";
$pre_namen = "";

$pre_text = "Hallo,

wir haben dir soeben einen Zugang zu Antragsgrün eingerichtet, wo du über unseren Entwurf mitdiskutieren kannst. Hier sind die Zugangsdaten:

%LINK%
BenutzerInnenname: %EMAIL%
Passwort: %PASSWORT%

Liebe Grüße,
  Das Antragsgrün-Team";

?>
<h1>BenutzerInnen</h1>

<div class="content">

	Wenn die Antragsgrün-Seite oder die Antrags-/Kommentier-Funktion nur für bestimmte Mitglieder zugänglich sein soll,
	kannst du hier die BenutzerInnen anlegen, die Zugriff haben sollen.<br>
	Sobald hier mindestens eine BenutzerIn angelegt ist, erscheint in den Veranstaltungs-Einstellungen im Punkt "Anträge stellen dürfen:"
	die neue Option "Nur Veranstaltungsreihen-BenutzerInnen".<br>
	<br>
	Um BenutzerInnen anzulegen, gib weiter unten die E-Mail-Adressen der Mitglieder ein.
	Diese Mitglieder bekommen daraufhin eine E-Mail mit ihrem Passwort zugesandt.<br>
	<br>
	Den Inhalt dieser Mail kannst du hier ebenfalls angeben. Achte nur darauf, dass in der E-Mail die Codes
	<strong>%EMAIL%</strong>, <strong>%PASSWORT%</strong> und <strong>%LINK%</strong> vorkommt, denn an diesen Stellen werden dann die Zugangsdaten
	der jeweiligen NutzerIn eingesetzt.<br>
	<br>
	<?php echo $msg; ?>
</div>

<h2>Bereits eingetragene BenutzerInnen</h2>
<div class="content">
	<?php if (count($accounts) == 0) echo '<em>noch keine</em>';
	else {
		echo '<ul>';
		foreach ($accounts as $account) {
			echo '<li>' . CHtml::encode($account->name) . ' (' . CHtml::encode($account->email) . ')</li>';
		}
		echo '</ul>';
	}
	?>
</div>

<h2>BenutzerInnen eintragen</h2>
<form method="POST" action="<?php echo CHtml::encode($this->createUrl("admin/index/namespacedAccounts")); ?>" class="content" id="nutzerInnen_anlegen_form">
	<label style="font-weight: bold; display: block; float: left; width: 300px;">
		E-Mail-Adressen:<br>
		<small style="font-weight: normal; display: block;">(genau eine E-Mail-Adresse pro Zeile!)</small>
		<textarea id="email_adressen" name="email_adressen" rows="15" style="width: 100%;"><?php echo CHtml::encode($pre_emails); ?></textarea>
	</label>

	<label style="font-weight: bold; display: block; float: left; width: 300px; margin-left: 25px;">
		Namen der BenutzerInnen:<br>
		<small style="font-weight: normal; display: block;">(Wichtig: Exakte Zuordnung zu den Zeilen links)</small>
		<textarea id="namen" name="namen" rows="15" style="width: 100%;"><?php echo CHtml::encode($pre_namen); ?></textarea>
	</label>


	<br style="clear: both;">
	<br>
	<label style="font-weight: bold; display: block;" for="email_text">Text der E-Mail:</label>
	<textarea id="email_text" name="email_text" rows="15" cols="80" style="width: 500px;"><?php echo CHtml::encode($pre_text); ?></textarea>
	<br><br>

	<script>
		$(function () {
			$("#nutzerInnen_anlegen_form").submit(function (ev) {
				var text = $("#email_text").val();
				if (text.indexOf("%EMAIL%") == -1) {
					alert("Im E-Mail-Text muss der Code %EMAIL% vorkommen.");
					ev.preventDefault();
				}
				if (text.indexOf("%PASSWORT%") == -1) {
					alert("Im E-Mail-Text muss der Code %PASSWORT% vorkommen.");
					ev.preventDefault();
				}
				if (text.indexOf("%LINK%") == -1) {
					alert("Im E-Mail-Text muss der Code %LINK% vorkommen.");
					ev.preventDefault();
				}

				var emails = $("#email_adressen").val().split("\n"),
					namen = $("#namen").val().split("\n");
				if (emails.length == 1 && emails[0] == "") {
					alert("Es wurden keine E-Mail-Adressen angegeben.");
					ev.preventDefault();
				}
				if (emails.length != namen.length) {
					alert("Es wurden nicht genauso viele Namen wie E-Mail-Adressen angegeben. Bitte achte darauf, dass für jede Zeile bei den E-Mail-Adressen exakt ein Name angegeben wird!");
					ev.preventDefault();
				}
			});
		})
	</script>

	<?php
	$this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'  => 'submit',
			'type'        => 'primary',
			'icon'        => 'ok white',
			'label'       => 'Anlegen',
			'htmlOptions' => array('name' => AntiXSS::createToken('eintragen')))
	);
	?>
</form>
