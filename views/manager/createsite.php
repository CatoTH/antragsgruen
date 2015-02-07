<?php
use app\models\forms\SiteCreateForm;
use yii\helpers\Html;
use yii\helpers\Url;

$controller = $this->context;

/**
 * @var $this yii\web\View
 * @var SiteCreateForm $model
 * @var string|null $error_string
 * @var \app\controllers\Base $controller
 */


$this->title = "Antragsgrün-Instanz anlegen";
$controller->layoutParams->addCSS('/css/formwizard.css');
$controller->layoutParams->addJS("/js/manager.js");
$controller->layoutParams->addOnLoadJS('$.SiteManager.createInstance();');

if ($error_string != "") {
    $error_string = '<div class="alert alert-error">' . $error_string . '</div>';
}

echo '<h1>Antragsgrün-Instanz anlegen</h1>
<div class="fuelux">';

echo Html::beginForm(Url::toRoute('manager/createsite'), 'post');

echo '<div id="AnlegenWizard" class="wizard">
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

echo $error_string;

foreach (\app\models\sitePresets\SitePresets::$PRESETS as $preset_id => $preset) {
    echo '<label class="site_preset">';
    echo Html::radio('SiteCreateForm[preset]', ($model->preset == $preset_id), ['value' => $preset_id]);
    echo '<span>' . Html::encode($preset::getTitle()) . '</span>';
    echo '</label><div class="site_explain">';
    echo $preset::getDescription();
    echo '</div>';
}

echo '
    <div class="weiter">
        <button class="btn btn-primary" id="weiter-1"><span class="icon-chevron-right"></span> Weiter</button>
    </div>
    <br><br>
    <strong><sup>1</sup> Hinweis:</strong>
    alle genannten Voreinstellungen können nachträglich unabhängig voneinander angepasst werden.
</div>

<div class="step-pane" id="step2">
    <br><br>';


echo '<div class="name">
<label><strong>Name der Veranstaltung / des Programms:</strong></label>';
echo Html::input('text', 'SiteCreateForm[title]', $model->title);
echo '</div>';

echo '<br><br>';

echo '<div class="url">
<label><strong>Unter folgender Adresse soll es erreichbar sein:</strong></label>';
echo Html::input('text', 'SiteCreateForm[subdomain]', $model->subdomain);
echo '<div style="font-size: 10px;">Für die Subdomain sind nur Buchstaben, Zahlen, "_" und "-" möglich.</div>
</div>';

echo '<br><br>';

echo '<label class="policy">';
echo Html::checkbox('SiteCreateForm[has_comments]', $model->has_comments);
echo 'BenutzerInnen können (Änderungs-)Anträge kommentieren
</label>
<br>';

echo '<label class="policy">';
echo Html::checkbox('SiteCreateForm[has_amendmends]', $model->has_amendmends);
echo 'BenutzerInnen können Änderungsanträge stellen
</label>
<br>';

echo '<label class="policy">';
echo Html::checkbox('SiteCreateForm[open_now]', $model->open_now);
echo 'Die neue Antragsgrün-Instanz soll sofort aufrufbar sein<br>
    &nbsp; &nbsp; &nbsp; (ansonsten: erst, wenn du exlizit den Wartungsmodus abschaltest)
</label>
<br><br>';


echo '
<div class="weiter">
    <button class="btn btn-primary" id="weiter-2"><span class="icon-chevron-right"></span> Weiter</button>
</div>

</div>
<div class="step-pane" id="step3">
<br>

<div class="kontakt">
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

/*
echo Html::checkbox('', $model->is_willing_to_pay)
 <?php
 echo CHtml::activeRadioButtonList($anlegenformmodel, "zahlung", VeranstaltungsreihenEinstellungen::$BEREIT_ZU_ZAHLEN);
 ?>
                <br>
*/
echo '</div>';

echo '<div class="weiter">

<button class="btn btn-success" type="submit"><i class="icon-ok"></i> Anlegen</button>
';


echo '</div>

            </div>
        </div>
';

echo Html::endForm();

echo '</div>';
