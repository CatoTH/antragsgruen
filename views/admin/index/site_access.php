<?php

use app\components\UrlHelper;
use app\models\db\User;
use yii\helpers\Html;
use app\models\settings\Site as SiteSettings;


/**
 * @var yii\web\View $this
 * @var \app\models\db\Site $site
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Zugang zur Seite';
$layout->addCSS('/css/backend.css');
$layout->addJS('/js/backend.js');
$layout->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Zugang');

$settings = $site->getSettings();

echo '<h1>Zugang zur Seite</h1>';
echo Html::beginForm('', 'post', ['id' => 'siteSettingsForm', 'class' => 'content adminForm form-horizontal']);

echo '<div class="checkbox">
  <label>' . Html::checkbox('forceLogin', $settings->forceLogin) . '
  Nur eingeloggte BenutzerInnen dürfen zugreifen (inkl. <em>lesen</em>)
</label>
</div>';


echo '<fieldset class="loginMethods"><legend>Folgende Login-Varianten sind möglich:</legend>';

$method = SiteSettings::LOGIN_STD;
echo '<div class="checkbox std">
  <label>' . Html::checkbox('login[]', in_array($method, $settings->loginMethods), ['value' => $method]) . '
  Standard-Antragsgrün-Accounts <small>(alle mit gültiger E-Mail-Adresse)</small>
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
  <label>' . Html::checkbox('login[]', in_array($method, $settings->loginMethods), ['value' => $method]) . '
  Sonstige Methoden <small>(OpenID, evtl. zufünftig auch Login per Facebook / Twitter)</small>
</label>
</div>';

echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div>';

echo '</fieldset>';


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
    echo '<li class="admin' . $admin->id . '">' .
        Html::encode($admin->name) . ' (' . Html::encode($admin->auth) . ')';
    if ($admin->id != $myself->id) {
        echo '<button class="link removeAdmin" type="button" data-id="' . $admin->id . '">';
        echo '<span class="glyphicon glyphicon-trash"></span>';
        echo '</button>';
    }
    echo "</li>";
}
echo '</ul>
</section>

<section class="content">
<h4>Neu eintragen</h4>
<div class="row">
    <label for="add_username" class="col-md-6">Wurzelwerk-BenutzerInnenname / E-Mail-Adresse:</label>
    </div>
    <div  class="row">
    <div class="col-md-6">
        <input type="text" name="username" value="" id="add_username" class="form-control">
    </div>
    </div>
<br>
<button type="submit" name="addAdmin" class="btn btn-primary">Hinzufügen</button>
</section>';
echo Html::endForm();


$layout->addOnLoadJS('$.AntragsgruenAdmin.siteAccessInit();');
