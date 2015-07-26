<?php

use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var \app\controllers\Base $controller
 * @var Consultation $consultation
 */

echo '<section class="showManagedUsers">';

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

echo $controller->showErrors();


echo '<div class="accountEditExplanation alert alert-info" role="alert">
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

if (count($consultation->userPrivileges) > 0) {
    echo Html::beginForm('', 'post', ['id' => 'accountsEditForm', 'class' => 'adminForm form-horizontal']);

    echo '<h3 class="lightgreen">' . 'Bereits eingetragene Benutzer_Innen' . '</h3>';

    echo '<table class="accountListTable table table-condensed">
<thead>
<tr>
<th class="nameCol">Name</th>
<th class="emailCol">Login</th>
<th class="accessViewCol">Lesen</th>
<th class="accessCreateCol">Anlegen</th>
</tr>
</thead>
<tbody>
';
    foreach ($consultation->userPrivileges as $privilege) {
        $checkView   = ($privilege->privilegeView == 1 ? 'checked' : '');
        $checkCreate = ($privilege->privilegeCreate == 1 ? 'checked' : '');
        echo '<tr class="user' . $privilege->userId . '">
    <td class="nameCol">' . Html::encode($privilege->user->name) . '</td>
    <td class="emailCol">' . Html::encode($privilege->user->getAuthName()) . '</td>
    <td class="accessViewCol">
        <label>
            <span class="sr-only">Leserechte</span>
            <input type="checkbox" name="access[' . $privilege->userId . '][]" value="view" ' . $checkView . '>
        </label>
    </td>
    <td class="accessCreateCol">
        <label>
            <span class="sr-only">Schreibrechte</span>
            <input type="checkbox" name="access[' . $privilege->userId . '][]" value="create" ' . $checkCreate . '>
        </label>
    </td>
    </tr>' . "\n";
    }
    echo '</tbody></table>

<div class="saveholder">
    <button type="submit" name="saveUsers" class="btn btn-primary">Speichern</button>
</div>
';
}


echo Html::endForm();


echo Html::beginForm('', 'post', ['id' => 'accountsCreateForm', 'class' => 'adminForm form-horizontal']);

echo '<h3 class="lightgreen">' . 'Benutzer_Innen eintragen' . '</h3>

<div class="row">
    <label class="col-md-6">
                E-Mail-Adressen:<br>
                <small>(genau eine E-Mail-Adresse pro Zeile!)</small>
                <textarea id="emailAddresses" name="emailAddresses" rows="15">' .
    Html::encode($preEmails) .
    '</textarea>
    </label>
    <label class="col-md-6">
                Namen der BenutzerInnen:<br>
                <small>(Wichtig: Exakte Zuordnung zu den Zeilen links)</small>
                <textarea id="names" name="names" rows="15">' . Html::encode($preNames) .
    '</textarea>
    </label>
</div>

<label for="emailText">Text der E-Mail:</label>
<textarea id="emailText" name="emailText" rows="15" cols="80">' . Html::encode($preText) . '</textarea>
<br><br>

<div class="saveholder">
    <button type="submit" name="addUsers" class="btn btn-primary">Berechtigen + E-Mail schicken</button>
</div>
';


echo Html::endForm();


echo '</div></section>';

$layout->addOnLoadJS('$.AntragsgruenAdmin.siteAccessUsersInit();');
