<?php

use app\components\UrlHelper;
use app\models\db\User;
use yii\helpers\Html;
use app\models\settings\Site as SiteSettings;

/**
 * @var yii\web\View $this
 * @var \app\models\db\Site $site
 * @var bool $policyWarning
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Zugang zur Seite';
$layout->addCSS('css/backend.css');
$layout->addJS('js/backend.js');
$layout->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Zugang');
$layout->loadFuelux();

$settings = $site->getSettings();

echo '<h1>Zugang zur Seite</h1>';

if ($policyWarning) {
    echo '<div class="accountEditExplanation alert alert-info alert-dismissible" role="alert">
<button type="button" class="close" data-dismiss="alert"
aria-label="Close"><span aria-hidden="true">&times;</span></button>
' . Html::beginForm('', 'post', ['id' => 'policyRestrictForm']) . '
<h3>Hinweis:</h3>
Die BenutzerInnenverwaltung unten kommt erst dann voll zur Geltung, wenn die Leserechte oder die Rechte zum Anlegen
 von Anträgen, Änderungsanträgen, Kommentaren etc. auf "Nur eingeloggte BenutzerInnen" gestellt werden. Aktuell ist
 das nicht der Fall.<br>
 <br>
 Falls die nur für unten eingetragene BenutzerInnen <em>sichtbar</em> sein soll, wähle die Einstellung gleich unterhalb
 dieses Hinweises aus. Falls die Seite für alle einsehbar sein soll, aber nur eingetragene BenutzerInnen
 Anträge etc. stellen können sollen, kannst du das hiermit automatisch einstellen:
 <div class="saveholder">
    <button type="submit" name="policyRestrictToUsers" class="btn btn-primary">Auf BenutzerInnen einschränken</button>
</div>' . Html::endForm() . '</div>';
}


echo Html::beginForm('', 'post', ['id' => 'siteSettingsForm', 'class' => 'content adminForm form-horizontal']);

$success = \Yii::$app->session->getFlash('success_login', null, true);
if ($success) {
    echo '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">Success:</span>
                ' . Html::encode($success) . '
            </div>';
}

echo '<div class="checkbox forceLogin">
  <label>' . Html::checkbox('forceLogin', $settings->forceLogin) . '
  Nur eingeloggte BenutzerInnen dürfen zugreifen (inkl. <em>lesen</em>)
</label>
</div>';

echo '<div class="checkbox managedUserAccounts">
  <label>' . Html::checkbox('managedUserAccounts', $settings->managedUserAccounts) . '
  Nur ausgewählten BenutzerInnen das Login erlauben <small class="showManagedUsers">(siehe unten)</small>
</label>
</div>';


echo '<fieldset class="loginMethods"><legend>Folgende Login-Varianten sind möglich:</legend>';

$method = SiteSettings::LOGIN_STD;
echo '<div class="checkbox std"><label>';
if (User::getCurrentUser()->getAuthType() == SiteSettings::LOGIN_STD) {
    echo Html::checkbox('login[]', true, ['value' => $method, 'disabled' => 'disabled']);
} else {
    echo Html::checkbox('login[]', in_array($method, $settings->loginMethods), ['value' => $method]);
}
echo ' Standard-Antragsgrün-Accounts <small>(alle mit gültiger E-Mail-Adresse)</small>
</label>
</div>';

if ($controller->getParams()->hasWurzelwerk) {
    $method = SiteSettings::LOGIN_WURZELWERK;
    echo '<div class="checkbox wurzelwerk">
  <label>' . Html::checkbox('login[]', in_array($method, $settings->loginMethods), ['value' => $method]) . '
  Wurzelwerk <small>(alle mit Wurzelwerk-Zugang)</small>
</label>
</div>';
}

$method = SiteSettings::LOGIN_EXTERNAL;
echo '<div class="checkbox external">
  <label>';
if (User::getCurrentUser()->getAuthType() == SiteSettings::LOGIN_EXTERNAL) {
    echo Html::checkbox('login[]', true, ['value' => $method, 'disabled']);
} else {
    echo Html::checkbox('login[]', in_array($method, $settings->loginMethods), ['value' => $method]);
}
echo '
  Sonstige Methoden <small>(OpenID, evtl. zufünftig auch Login per Facebook / Twitter)</small>
</label>
</div>';

echo '</fieldset>';

echo '<div class="saveholder">
<button type="submit" name="saveLogin" class="btn btn-primary">Speichern</button>
</div>';

echo Html::endForm();

if ($controller->consultation) {
    $consultation = $controller->consultation;
    include('site_access_accounts.php');
}


echo Html::beginForm('', 'post', ['id' => 'adminForm', 'class' => 'adminForm form-horizontal']);
echo '<h2 class="green">Administrator_Innen der Reihe</h2>
    <section class="content">
    <ul style="margin-top: 10px;">';

$myself = User::getCurrentUser();
foreach ($site->admins as $admin) {
    echo '<li class="admin' . $admin->id . '">';
    echo Html::encode($admin->getAuthName());
    if ($admin->name != '') {
        echo ' (' . Html::encode($admin->name) . ')';
    }
    if ($admin->id != $myself->id) {
        echo '<button class="link removeAdmin" type="button" data-id="' . $admin->id . '">';
        echo '<span class="glyphicon glyphicon-trash"></span>';
        echo '</button>';
    }
    echo "</li>";
}
echo '</ul>

<br>

<h4>Neu eintragen</h4>
<div class="row">
    <div class="col-md-3">';

$options = [
    'wurzelwerk' => 'Wurzelwerk-Name:',
    'email'      => 'E-Mail-Adresse:',
];
echo \app\components\HTMLTools::fueluxSelectbox('addType', $options);
echo '</div>
<div class="col-md-4">
    <input type="text" name="addUsername" value="" id="addUsername" class="form-control"
    title="Wurzelwerk-BenutzerInnenname / E-Mail-Adresse" placeholder="Name" required>
</div>
<div class="col-md-3">
    <button type="submit" name="addAdmin" class="btn btn-primary">Hinzufügen</button>
</div>
</div>
<br><br>
</section>';
echo Html::endForm();


$layout->addOnLoadJS('$.AntragsgruenAdmin.siteAccessInit();');
