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

	Wenn die Antragsgrün-Seite oder die Antrags-/Kommentier-Funktion nur für bestimmte Mitglieder zugänglich sein soll, kannst du hier die BenutzerInnen anlegen, die Zugriff haben
	sollen.<br>
	<br>
	Trage dazu unten die E-Mail-Adressen der Mitglieder. Diese Mitglieder bekommen daraufhin eine E-Mail mit ihrem Passwort zugesandt.<br>
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
			echo '<li>' . CHtml::encode($account->email) . '</li>';
		}
		echo '</ul>';
	}
	?>
</div>

<h2>BenutzerInnen eintragen</h2>
<form method="POST" action="<?php echo CHtml::encode($this->createUrl("admin/index/namespacedAccounts")); ?>" class="content" id="nutzerInnen_anlegen_form">
	<label style="font-weight: bold; display: block;" for="email_adressen">E-Mail-Adressen:</label>
	(bitte gib genau eine E-Mail-Adresse pro Zeile an!)<br>
	<textarea id="email_adressen" name="email_adressen" rows="15" cols="80" style="width: 500px;"><?php echo CHtml::encode($pre_emails); ?></textarea>


	<br>
	<label style="font-weight: bold; display: block;" for="email_text">Text der E-Mail:</label>
	<textarea id="email_text" name="email_text" rows="15" cols="80" style="width: 500px;"><?php echo CHtml::encode($pre_text); ?></textarea>
	<br><br>

	<script>
		$(function() {
			$("#nutzerInnen_anlegen_form").submit(function(ev) {
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
			});
		})
	</script>

	<?php
	$this->widget('bootstrap.widgets.TbButton', array(
		'buttonType' => 'submit',
		'type' => 'primary',
		'icon' => 'ok white',
		'label' => 'Anlegen',
		'htmlOptions' => array('name' => AntiXSS::createToken('eintragen')))
	);
	?>
</form>
