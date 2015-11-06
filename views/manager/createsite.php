<?php
use app\models\forms\SiteCreateForm;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var SiteCreateForm $model
 * @var array $errors
 * @var \app\controllers\Base $controller
 */

$controller = $this->context;

$this->title = 'Antragsgrün-Seite anlegen';
$controller->layoutParams->addCSS('css/formwizard.css');
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->addJS("js/manager.js");
$controller->layoutParams->addOnLoadJS('$.SiteManager.createInstance();');


echo '<h1>Antragsgrün-Seite anlegen</h1>
<div class="fuelux">';

echo Html::beginForm(Url::toRoute('manager/createsite'), 'post', ['class' => 'siteCreate']);

echo '<div id="SiteCreateWizard" class="wizard">
            <ul class="steps">
                <li data-target="#step1" class="active">
                    <span class="badge badge-info">1</span>Einsatzzweck<span class="chevron"></span>
                </li>
                <li data-target="#step2">
                    <span class="badge">2</span>Details<span class="chevron"></span>
                </li>
                <li data-target="#step3">
                    <span class="badge">3</span>Kontaktdaten<span class="chevron"></span>
                </li>
            </ul>
        </div>
        <div class="content">
            <div class="step-pane active" id="step1">';
if (count($errors) > 0) {
    echo '<div class="alert alert-danger" role="alert">
        <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
        <span class="sr-only">Error:</span>' . nl2br(Html::encode(implode("\n", $errors))) .
        '</div>';
}

foreach (\app\models\sitePresets\SitePresets::$PRESETS as $presetId => $preset) {
    $defaults = json_encode($preset::getDetailDefaults());
    echo '<label class="sitePreset" data-defaults="' . Html::encode($defaults) . '">';
    echo Html::radio('SiteCreateForm[preset]', ($model->preset == $presetId), ['value' => $presetId]);
    echo '<span>' . Html::encode($preset::getTitle()) . '</span>';
    echo '</label><div class="sitePresetInfo">';
    echo $preset::getDescription();
    echo '</div>';
}

echo '
    <div class="next">
        <button class="btn btn-primary" id="next-1"><span class="icon-chevron-right"></span> Weiter</button>
    </div>
    <br><br>
    <strong><sup>1</sup> Hinweis:</strong>
    alle genannten Voreinstellungen können nachträglich unabhängig voneinander angepasst werden.
</div>

<div class="step-pane" id="step2">
    <br><br>';

echo '<div class="row"><div class="col-md-7">';

echo '<div class="form-group">';
echo '<label class="name" for="siteOrganization">Name der Organisation:</label>';
$opts = ['id' => 'siteOrganization', 'class' => 'form-control'];
echo Html::input('text', 'SiteCreateForm[organization]', $model->title, $opts);
echo '</div>';

echo '<div class="form-group">';
echo '<label class="name" for="siteTitle">Name der Veranstaltung / des Programms:</label>';
echo Html::input('text', 'SiteCreateForm[title]', $model->title, ['id' => 'siteTitle', 'class' => 'form-control']);
echo '</div>';

/** @var \app\models\settings\AntragsgruenApp $params */
$params = \yii::$app->params;
$input  = Html::input('text', 'SiteCreateForm[subdomain]', $model->subdomain, ['id' => 'subdomain']);
echo '<div class="form-group">';
echo '<label class="url" for="subdomain">Unter folgender Adresse soll es erreichbar sein:</label>';
echo '<div class="fakeurl">';
if (strpos($params->domainSubdomain, '<subdomain:[\w_-]+>') !== false) {
    echo str_replace('&lt;subdomain:[\w_-]+&gt;', $input, Html::encode($params->domainSubdomain));
} else {
    echo $input;
}
echo '</div>';
echo '<div class="labelSubInfo">Für die Subdomain sind nur Buchstaben, Zahlen, "_" und "-" möglich.</div>
</div>';

echo '</div></div><br>';

echo '<label class="policy">';
echo Html::checkbox('SiteCreateForm[hasComments]', $model->hasComments, ['class' => 'hasComments']);
echo 'BenutzerInnen können (Änderungs-)Anträge kommentieren
</label>';

echo '<label class="policy">';
echo Html::checkbox('SiteCreateForm[hasAmendments]', $model->hasAmendments, ['class' => 'hasAmendments']);
echo 'BenutzerInnen können Änderungsanträge stellen
</label>';

echo '<label class="policy">';
echo Html::checkbox('SiteCreateForm[openNow]', $model->openNow, ['class' => 'openNow']);
echo 'Die neue Antragsgrün-Seite soll sofort aufrufbar sein<br>
    <span class="labelSubInfo">(ansonsten: erst, wenn du exlizit den Wartungsmodus abschaltest)</span>
</label>
<br>';


echo '
<div class="next">
    <button class="btn btn-primary" id="next-2"><span class="icon-chevron-right"></span> Weiter</button>
</div>

</div>
<div class="step-pane" id="step3">
<br>

<div class="contact">
<label>
    <strong>Kontaktadresse:</strong> <small>(Name, E-Mail, postalische Adresse; wird standardmäßig im Impressum genannt)</small>';
echo Html::textarea('SiteCreateForm[contact]', $model->contact, ['rows' => 5]);
echo '</label>';

echo '
</div>
<br><br>

    <div class="zahlung">
    <strong>Wärst du bereit, einen freiwilligen Beitrag über 30€ für den Betrieb von Antragsgrün zu leisten?</strong><br>
    (Wenn ja, schicken wir dir eine Rechnung an die oben eingegebene Adresse)<br>
';
foreach (\app\models\settings\Site::getPaysValues() as $payId => $payName) {
    echo '<div class="radio"><label>';
    $checked = ($model->isWillingToPay !== null && $model->isWillingToPay == $payId);
    echo Html::radio('SiteCreateForm[isWillingToPay]', $checked, ['value' => $payId, 'required' => 'required']);
    echo Html::encode($payName);
    echo '</label></div>';
}

echo '</div>';

echo '<div class="next">

<button class="btn btn-success" type="submit" name="create"><i class="icon-ok"></i> Anlegen</button>
';


echo '</div>

            </div>
        </div>
';

echo Html::endForm();

echo '</div>';
