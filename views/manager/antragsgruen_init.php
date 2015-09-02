<?php

use app\components\HTMLTools;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\forms\AntragsgruenInitForm $form
 * @var string $delInstallFileCmd
 * @var bool $installFileDeletable
 */


$controller  = $this->context;
$this->title = 'Antragsgrün installieren';

/** @var \app\controllers\admin\IndexController $controller */
$controller            = $this->context;
$layout                = $controller->layoutParams;
$layout->loadFuelux();
$layout->robotsNoindex = true;
$layout->addJS('js/manager.js');
$layout->addOnLoadJS('$.SiteManager.antragsgruenInit();');

echo '<h1>' . 'Antragsgrün installieren' . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'antragsgruenInitForm form-horizontal fuelux']);

echo '<div class="content">';
echo $controller->showErrors();


if ($form->isConfigured()) {
    $errors = $form->verifyConfiguration();
    if (count($errors) > 0) {
        echo '<div class="alert alert-danger" role="alert">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">Error:</span>
                ' . nl2br(Html::encode(implode("\n", $errors))) . '
            </div>';
    } else {
        echo '<div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
        <span class="sr-only">Success:</span>';
        echo 'Die Grundkonfiguration ist abgeschlossen.<br><br>';
        if ($installFileDeletable) {
            echo '<div class="saveholder">';
            echo '<button class="btn btn-success" name="finishInit">';
            echo 'Installationsmodus beenden';
            echo '</button></div>';
        } else {
            echo 'Um den Installationsmodus zu beenden, lösche die Datei config/INSTALLING. ';
            echo 'Je nach Betriebssystem könnte der Befehl dazu z.B. folgendermaßen lauten:<pre>';
            echo Html::encode($delInstallFileCmd);
            echo '</pre>';
        }
        echo '</div>';
    }
} else {
    echo '<div class="alert alert-info" role="alert">
        <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
        <span class="sr-only">Welcome:</span>
        ' . 'Willkommen!' . '
    </div>';
}

echo '</div>';


echo '<h2 class="green">' . 'Adresse' . '</h2>';
echo '<div class="content">';


echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="siteUrl">' . 'URL' . ':</label>
    <div class="col-sm-8">
    <input type="text" required name="siteUrl" placeholder="https://..."
        value="' . Html::encode($form->siteUrl) . '" class="form-control" id="siteUrl">
    </div>
</div>';

echo '</div>';


echo '<h2 class="green">' . 'Datenbank' . '</h2>';
echo '<div class="content">';

echo '<div class="form-group sqlType">
    <label class="col-sm-4 control-label" for="sqlType">' . 'Datenbank-Typ' . ':</label>
    <div class="col-sm-8">';
echo HTMLTools::fueluxSelectbox(
    'sqlType',
    [
        'mysql'      => 'MySQL / MariaDB',
    ],
    $form->sqlType,
    ['id' => 'sqlType']
);
echo '</div>
</div>';

echo '<div class="form-group sqlOption mysqlOption">
    <label class="col-sm-4 control-label" for="sqlHost">' . 'Servername' . ':</label>
    <div class="col-sm-8">
        <input type="text" required name="sqlHost" placeholder="localhost"
        value="' . Html::encode($form->sqlHost) . '" class="form-control" id="sqlHost">
    </div>
</div>';

echo '<div class="form-group sqlOption mysqlOption">
    <label class="col-sm-4 control-label" for="sqlUsername">' . 'Benutzername' . ':</label>
    <div class="col-sm-8">
        <input type="text" required name="sqlUsername"
        value="' . Html::encode($form->sqlUsername) . '" class="form-control" id="sqlUsername">
    </div>
</div>';

echo '<div class="form-group sqlOption mysqlOption">
    <label class="col-sm-4 control-label" for="sqlPassword">' . 'Passwort' . ':</label>
    <div class="col-sm-8">
        <input type="password" required name="sqlPassword"
        value="' . Html::encode($form->sqlPassword) . '" class="form-control" id="sqlPassword">
    </div>
</div>';

echo '<div class="form-group sqlOption mysqlOption">
    <label class="col-sm-4 control-label" for="sqlDB">' . 'Datenbank-Name' . ':</label>
    <div class="col-sm-8">
        <input type="text" required name="sqlDB"
        value="' . Html::encode($form->sqlDB) . '" class="form-control" id="sqlDB">
    </div>
</div>';

$verifyDBUrl = \app\components\UrlHelper::createUrl('manager/antragsgrueninitdbtest');
echo '<div class="testDB">
<button type="button" name="testDB" class="btn btn-default testDBcaller"
data-url="' . Html::encode($verifyDBUrl) . '">Datenbank testen</button>
<div class="testDBRpending hidden">Prüfe...</div>
<div class="testDBresult hidden">Ergebnis: <span class="result"></span></div>
</div>';

echo '</div>';


echo '<h2 class="green">' . 'Admin-Zugang' . '</h2>';
echo '<div class="content">';

echo '</div>';


echo Html::endForm();
