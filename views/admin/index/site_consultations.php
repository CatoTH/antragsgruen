<?php

use app\components\UrlHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var \app\models\db\Site $site
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Veranstaltungen verwalten';
$layout->addCSS('/css/backend.css');
$layout->addJS('/js/backend.js');
$layout->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Veranstaltungen');
$layout->loadFuelux();

$settings = $site->getSettings();

echo '<h1>Veranstaltungen</h1>';
echo Html::beginForm('', 'post', ['class' => 'consultationEditForm']);
echo '<h2 class="green">' . 'Angelegte Veranstaltungen' . '</h2>';
echo '<div class="content"><ul id="consultationsList">';
foreach ($site->consultations as $consultation) {
    $isStandard = ($consultation->id == $site->currentConsultationId);
    $params = ['subdomain' => $site->subdomain, 'consultationPath' => $consultation->urlPath];

    echo '<li class="consultation' . $consultation->id . '">';

    echo '<div class="stdbox">';
    if ($isStandard) {
        echo '<strong><span class="glyphicon glyphicon-ok" style="color: green;"></span> ' .
            'Standard-Veranstaltung' . '</strong>';
    } else {
        echo '<button type="submit" name="setStandard[' . $consultation->id . ']" class="link">' .
            'Als Standard setzen</button>';
    }
    echo '</div>';

    echo '<h3>';
    echo Html::encode($consultation->title) . ' <small>(' . Html::encode($consultation->titleShort) . ')</small>';
    echo '</h3>';

    echo '<div class="homeLink">';
    $url = Url::toRoute(array_merge(['consultation/index'], $params));
    echo '<a href="' . Html::encode($url) . '"><span class="glyphicon glyphicon-chevron-right"></span> ' .
        'Zur Seite' . '</a>';
    echo '</div><div class="adminLink">';
    $url = Url::toRoute(array_merge(['admin/index'], $params));
    echo '<a href="' . Html::encode($url) . '"><span class="glyphicon glyphicon-chevron-right"></span> ' .
        'Zur Administration' . '</a>';
    echo '</div>';

    echo '</li>';
}
echo '</ul></div>';
echo Html::endForm();


echo Html::beginForm('', 'post', ['class' => 'consultationCreateForm form-horizontal']);
$templates = [];
foreach ($site->consultations as $cons) {
    $templates[$cons->id] = $cons->title;
}

echo '<h2 class="green">' . 'Veranstaltung anlegen' . '</h2>

<div class="content">

<div class="form-group">
    <label for="newTitle" class="col-md-4 control-label">Titel der Veranstaltung:</label>
    <div class="col-md-8">
        <input type="text" class="form-control" value="" name="newConsultation[title]">
    </div>
</div>

<div class="form-group">
    <label for="newPath" class="col-md-4 control-label">Internet-Adresse:</label>
    <div class="col-md-8 fakeUrl">';

$input = '<input type="text" class="form-control" value="" name="newConsultation[urlPath]">';
$url = Url::toRoute(['consultation/index', 'subdomain' => $site->subdomain, 'consultationPath' => '--CON--']);
$url = UrlHelper::absolutizeLink($url);
echo str_replace('--CON--', $input, $url);

echo '</div>

<div class="form-group">
    <label for="newTemplate" class="col-md-4 control-label">Einstellungen übernehmen von:</label>
    <div class="col-md-8">' .
    \app\components\HTMLTools::fueluxSelectbox('newConsultation[template]', $templates) .
    '</div>
</div>

<div class="form-group">
    <div class="label col-md-4 control-label">Standard:</div>
    <div class="col-md-8 checkbox">
        <label>
            <input type="checkbox" name="newConsultation[setStandard]" id="newSetStandard">
            Sofort als Standard-Veranstaltung festlegen
        </label>
    </div>
</div>

<div class="saveholder">
    <button type="submit" name="createConsultation" class="btn btn-primary">Veranstaltung anlegen</button>
</div>

</div>

</div>';
echo Html::endForm();
