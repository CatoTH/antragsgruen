<?php

use app\components\HTMLTools;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\forms\AntragsgruenInitForm $form
 * @var string $delInstallFileCmd
 * @var bool $installFileDeletable
 * @var bool $editable
 * @var string $makeEditabeCommand
 */


$controller  = $this->context;
$this->title = 'Antragsgrün installieren';

/** @var \app\controllers\admin\IndexController $controller */
$controller            = $this->context;
$layout                = $controller->layoutParams;
$layout->loadFuelux();
$layout->robotsNoindex = true;
$layout->addJS('js/manager.js');
$layout->addCSS('css/manager.css');
$layout->addOnLoadJS('$.SiteManager.antragsgruenInit();');

echo '<h1>' . 'Antragsgrün installieren' . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'antragsgruenInitForm form-horizontal fuelux']);

echo '<div class="content">';
echo $controller->showErrors();

if (!$editable) {
    echo '<div class="alert alert-danger" role="alert">';
    echo 'Die Einstellungen können nicht bearbeitet werden, da die Datei config/config.json nicht bearbeitbar ist.
    <br>Das lässt sich mit folgendem Befehl (oder ähnlich, je nach Betriebssystem) auf der Kommandozeile beheben:
    <br><br><pre>';
    echo Html::encode($makeEditabeCommand);
    echo '</pre>';
    echo '</div>';
}


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
        echo 'Die Grundkonfiguration ist abgeschlossen.';
        echo '</div>';

        if (!$form->tablesAreCreated()) {
            echo '<div class="alert alert-info" role="alert">
        <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
        <span class="sr-only">Hint:</span>';

            echo '<strong>Die Datenbanktabellen sind allerdings noch nicht angelegt.</strong>
            Um das zu erledigen, nutze entweder die Funktion unten, oder rufe den Kommandozeilenbefehl auf:
            <pre>./yii database/create</pre>
            Die SQL-Skripte, um die Tabellen händisch zu erzeugen, liegen hier:
            <pre>assets/db/create.sql</pre>';
            echo '</div>';
        }

        echo '<div class="alert alert-info" role="alert">
        <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>';

        if ($installFileDeletable) {
            echo '<div class="saveholder">';
            echo '<button class="btn btn-success" name="finishInit">';
            echo 'Installationsmodus beenden';
            echo '</button></div>';
        } else {
            echo str_replace('%DELCMD%', Html::encode($delInstallFileCmd), 'Um den Installationsmodus zu beenden,
                lösche die Datei config/INSTALLING.
                Je nach Betriebssystem könnte der Befehl dazu z.B. folgendermaßen lauten:<pre>%DELCMD%</pre>
                Rufe danach diese Seite hier neu auf.
                ');
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


echo '<div><label>';
echo Html::checkbox('prettyUrls', $form->prettyUrls, ['id' => 'prettyUrls']);
echo '"Hübsche" URLs (benötigt URL-Rewriting)';
echo '</label></div>';


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
        <input type="text" name="sqlHost" placeholder="localhost"
        value="' . Html::encode($form->sqlHost) . '" class="form-control" id="sqlHost">
    </div>
</div>';

echo '<div class="form-group sqlOption mysqlOption">
    <label class="col-sm-4 control-label" for="sqlUsername">' . 'Benutzername' . ':</label>
    <div class="col-sm-8">
        <input type="text" name="sqlUsername"
        value="' . Html::encode($form->sqlUsername) . '" class="form-control" id="sqlUsername">
    </div>
</div>';

echo '<div class="form-group sqlOption mysqlOption">
    <label class="col-sm-4 control-label" for="sqlPassword">' . 'Passwort' . ':</label>
    <div class="col-sm-8">
        <input type="password" name="sqlPassword"
        value="' . Html::encode($form->sqlPassword) . '" class="form-control" id="sqlPassword">
    </div>
</div>';

echo '<div class="form-group sqlOption mysqlOption">
    <label class="col-sm-4 control-label" for="sqlDB">' . 'Datenbank-Name' . ':</label>
    <div class="col-sm-8">
        <input type="text" name="sqlDB"
        value="' . Html::encode($form->sqlDB) . '" class="form-control" id="sqlDB">
    </div>
</div>';

$verifyDBUrl = \app\components\UrlHelper::createUrl('manager/antragsgrueninitdbtest');
echo '<div class="testDB">
<button type="button" name="testDB" class="btn btn-default testDBcaller"
data-url="' . Html::encode($verifyDBUrl) . '">Datenbank testen</button>
<div class="testDBRpending hidden">Prüfe...</div>
<div class="testDBerror hidden">
    <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
    <span class="result"></span>
</div>
<div class="testDBsuccess hidden">
    <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
    ' . 'Erfolg' . '
</div>
</div>';


echo '<div class="createTables"><label>';
echo Html::checkbox('sqlCreateTables', $form->sqlCreateTables, ['id' => 'sqlCreateTables']);
echo 'Notwendige Datenbanktabellen automatisch anlegen';
echo '</label>';
echo '<div class="alreadyCreatedHint">' . '(nicht nötig, bereits vorhanden; aber auch nicht schädlich)' . '</div>';
echo '</div>';
echo '</div>';

if ($form->sqlHost != '' || $form->sqlFile != '' || $form->sqlUsername != '') {
    $layout->addOnLoadJS('$(".testDBcaller").click();');
}




echo '<h2 class="green">' . 'Admin-Zugang' . '</h2>';
echo '<div class="content">';

if ($form->hasAdminAccount()) {
    echo '<strong>Bereits angelegt.</strong><br>';
    echo 'Falls das ein Fehler ist: entferne die "adminUserIds"-Einträge in der config/config.json.';
} else {

}

echo '</div>';


echo '<div class="content saveholder">
<button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div>';


echo Html::endForm();
