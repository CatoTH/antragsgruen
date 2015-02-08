<?php
use app\models\forms\SiteCreateForm;
use yii\helpers\Html;
use yii\helpers\Url;

$controller = $this->context;

/**
 * @var $this yii\web\View
 * @var SiteCreateForm $model
 * @var array $errors
 * @var \app\controllers\Base $controller
 */


$this->title = "Antragsgrün-Instanz anlegen";
$controller->layoutParams->addCSS('/css/formwizard.css');
$controller->layoutParams->addCSS('/css/manager.css');
$controller->layoutParams->addJS("/js/manager.js");
$controller->layoutParams->addOnLoadJS('$.SiteManager.createInstance();');


echo '<h1>Antragsgrün-Instanz anlegen</h1>
<div class="fuelux">';

$form = \yii\widgets\ActiveForm::begin(['options' => ['class' => 'siteCreate']]);
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
        <div class="content step-content">
            <div class="step-pane active" id="step1">';
if (count($errors) > 0) {
    echo '<div class="alert alert-danger" role="alert">
        <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
        <span class="sr-only">Error:</span>' . nl2br(Html::encode(implode("\n", $errors))) .
        '</div>';
}

foreach (\app\models\sitePresets\SitePresets::$PRESETS as $preset_id => $preset) {
    echo '<label class="sitePreset">';
    echo Html::radio('SiteCreateForm[preset]', ($model->preset == $preset_id), ['value' => $preset_id]);
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


echo '<label class="name">Name der Veranstaltung / des Programms:';
echo Html::input('text', 'SiteCreateForm[title]', $model->title);
echo '</label>';

echo '<br><br>';

echo '<label class="url">Unter folgender Adresse soll es erreichbar sein:';
echo Html::input('text', 'SiteCreateForm[subdomain]', $model->subdomain, ['id' => 'subdomain']);
echo '<div class="labelSubInfo">Für die Subdomain sind nur Buchstaben, Zahlen, "_" und "-" möglich.</div>
</label>';

echo '<br><br>';

echo '<label class="policy">';
echo Html::checkbox('SiteCreateForm[hasComments]', $model->hasComments);
echo 'BenutzerInnen können (Änderungs-)Anträge kommentieren
</label>';

echo '<label class="policy">';
echo Html::checkbox('SiteCreateForm[hasAmendments]', $model->hasAmendments);
echo 'BenutzerInnen können Änderungsanträge stellen
</label>';

echo '<label class="policy">';
echo Html::checkbox('SiteCreateForm[openNow]', $model->openNow);
echo 'Die neue Antragsgrün-Instanz soll sofort aufrufbar sein<br>
    <div class="labelSubInfo">(ansonsten: erst, wenn du exlizit den Wartungsmodus abschaltest)</div>
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
    <strong>Kontaktadresse:</strong> (postalisch + E-Mail; wird standardmäßig im Impressum genannt)';
echo Html::textarea('SiteCreateForm[contact]', $model->contact, ['rows' => 5]);
echo '</label>';

echo '
</div>
<br><br>

    <div class="zahlung">
    <strong>Wärest du bereit, einen freiwilligen Beitrag über 20€ an die Netzbegrünung zu leisten?</strong><br>
    (Wenn ja, schicken wir dir eine Rechnung an die oben eingegebene Adresse)<br>
';
foreach (\app\models\SiteSettings::getPaysValues() as $payId => $payName) {
    echo '<div class="radio"><label>';
    $checked = $model->isWillingToPay === $payId;
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
