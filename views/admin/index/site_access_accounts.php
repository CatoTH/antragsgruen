<?php

use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var Consultation $consultation
 */

echo '<h2 class="green">' . 'Benutzer_Innen-Accounts' . '</h2>';
echo '<div class="content">';


$preEmails = '';
$preNames  = '';
$preText   = 'Hallo,

wir haben dir soeben einen Zugang zu Antragsgrün eingerichtet, wo du über unseren Entwurf mitdiskutieren kannst.
Hier sind die Zugangsdaten:

%LINK%
BenutzerInnenname: %EMAIL%
Passwort: %PASSWORT%

Liebe Grüße,
  Das Antragsgrün-Team';

echo 'Wenn die Antragsgrün-Seite oder die Antrags-/Kommentier-Funktion nur für bestimmte Mitglieder zugänglich sein soll,
kannst du hier die BenutzerInnen anlegen, die Zugriff haben sollen.<br>
Sobald hier mindestens eine BenutzerIn angelegt ist, erscheint in den Veranstaltungs-Einstellungen im Punkt "Anträge stellen dürfen:"
die neue Option "Nur Veranstaltungsreihen-BenutzerInnen".<br>
<br>
Um BenutzerInnen anzulegen, gib weiter unten die E-Mail-Adressen der Mitglieder ein.
Diese Mitglieder bekommen daraufhin eine E-Mail mit ihrem Passwort zugesandt.<br>
<br>
Den Inhalt dieser Mail kannst du hier ebenfalls angeben. Achte nur darauf, dass in der E-Mail die Codes
<strong>%EMAIL%</strong>, <strong>%PASSWORT%</strong> und <strong>%LINK%</strong> vorkommt, denn an diesen Stellen werden dann die Zugangsdaten
der jeweiligen NutzerIn eingesetzt.<br>';


echo Html::beginForm('', 'post', ['id' => 'accountsEditForm', 'class' => 'adminForm form-horizontal']);

echo '<h3>' . 'Bereits eingetragene BenutzerInnen' . '</h3>';

echo '<ul>';
foreach ($consultation->userPrivileges as $privilege) {
    echo '<li>';
    var_dump($privilege);
    echo '</li>';
}
echo '</ul>';

echo Html::endForm();


echo Html::beginForm('', 'post', ['id' => 'accountsCreateForm', 'class' => 'adminForm form-horizontal']);

echo '<h3>' . 'BenutzerInnen eintragen' . '</h3>

<div class="row">
    <div class="col-md-6">
        <label>
                E-Mail-Adressen:<br>
                <small style="font-weight: normal; display: block;">(genau eine E-Mail-Adresse pro Zeile!)</small>
                <textarea id="email_adressen" name="email_adressen" rows="15" style="width: 100%;">' .
    Html::encode($preEmails) .
    '</textarea>
        </label>
    </div>
    <div class="col-md-6">
        <label>
                Namen der BenutzerInnen:<br>
                <small style="font-weight: normal; display: block;">(Wichtig: Exakte Zuordnung zu den Zeilen links)</small>
                <textarea id="namen" name="namen" rows="15" style="width: 100%;">' . Html::encode($preNames) .
    '</textarea>
        </label>
    </div>
</div>

<label style="font-weight: bold; display: block;" for="emailText">Text der E-Mail:</label>
<textarea id="emailText" name="emailText" rows="15" cols="80">' . Html::encode($preText) .
    '</textarea>
<br><br>

<div class="saveholder">
    <button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div>
';


echo Html::endForm();


echo '</div>';

$layout->addOnLoadJS('$.AntragsgruenAdmin.siteAccessUsersInit();');
