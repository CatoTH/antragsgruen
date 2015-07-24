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

wir haben dir soeben Zugang zu unserer Antragsgrün-Seite eingerichtet, ' .
    'auf der du über unseren Entwurf mitdiskutieren kannst.
Hier ist der Zugang:

%LINK%
%ACCOUNT%

Liebe Grüße,
  Das Antragsgrün-Team';

echo Html::beginForm('', 'post', ['id' => 'accountsEditForm', 'class' => 'adminForm form-horizontal']);

echo '<div class="explanation alert alert-info" role="alert">
<h3>Erklärung:</h3>
Wenn die Antragsgrün-Seite oder die Antrags-/Kommentier-Funktion nur für bestimmte Mitglieder zugänglich sein soll,
kannst du hier die BenutzerInnen anlegen, die Zugriff haben sollen.<br>
<br>
Um BenutzerInnen anzulegen, gib weiter unten die E-Mail-Adressen der Mitglieder ein.
Diese Mitglieder bekommen daraufhin eine Benachrichtigungs-E-Mail zugesandt.<br>
Falls sie noch keinen eigenen Zugang auf Antragsgrün hatten, wird automatisch einer eingerichtet
und an der Stelle von <strong>%ACCOUNT%</strong> erscheinen die Zugangsdaten
(ansonsten verschwindet das %ACCOUNT% ersatzlos).<br>
<strong>%LINK%</strong> wird immer durch einen Link auf die Antragsgrün-Seite ersetzt.
</div>';

echo '<h3 class="lightgreen">' . 'Bereits eingetragene BenutzerInnen' . '</h3>';

echo '<ul>';
foreach ($consultation->userPrivileges as $privilege) {
    echo '<li>';
    var_dump($privilege);
    echo '</li>';
}
echo '</ul>';

echo Html::endForm();


echo Html::beginForm('', 'post', ['id' => 'accountsCreateForm', 'class' => 'adminForm form-horizontal']);

echo '<h3 class="lightgreen">' . 'BenutzerInnen eintragen' . '</h3>

<div class="row">
    <label class="col-md-6">
                E-Mail-Adressen:<br>
                <small>(genau eine E-Mail-Adresse pro Zeile!)</small>
                <textarea id="email_adressen" name="email_adressen" rows="15">' .
    Html::encode($preEmails) .
    '</textarea>
    </label>
    <label class="col-md-6">
                Namen der BenutzerInnen:<br>
                <small>(Wichtig: Exakte Zuordnung zu den Zeilen links)</small>
                <textarea id="namen" name="namen" rows="15">' . Html::encode($preNames) .
    '</textarea>
    </label>
</div>

<label for="emailText">Text der E-Mail:</label>
<textarea id="emailText" name="emailText" rows="15" cols="80">' . Html::encode($preText) . '</textarea>
<br><br>

<div class="saveholder">
    <button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div>
';


echo Html::endForm();


echo '</div>';

$layout->addOnLoadJS('$.AntragsgruenAdmin.siteAccessUsersInit();');
