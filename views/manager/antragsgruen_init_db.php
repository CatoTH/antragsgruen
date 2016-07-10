<?php

use app\components\HTMLTools;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\forms\AntragsgruenInitDb $form
 * @var string $delInstallFileCmd
 * @var bool $installFileDeletable
 * @var bool $editable
 * @var string $makeEditabeCommand
 */


$controller  = $this->context;
$this->title = \yii::t('manager', 'title_install');

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$layout->loadFuelux();
$layout->robotsNoindex = true;
$layout->addJS('js/manager.js');
$layout->addCSS('css/manager.css');
$layout->addOnLoadJS('$.SiteManager.antragsgruenInitDb();');

echo '<h1>' . \yii::t('manager', 'title_install') . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'antragsgruenInitForm form-horizontal fuelux']);

echo '<div class="content">';
echo $controller->showErrors();

if (!$editable) {
    echo '<div class="alert alert-danger" role="alert">';
    echo \Yii::t('manager', 'err_settings_ro');
    echo '<br><br><pre>';
    echo Html::encode($makeEditabeCommand);
    echo '</pre>';
    echo '</div>';
}


if ($form->isConfigured()) {
    try {
        $form->verifyDBConnection();

        echo '<div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
        <span class="sr-only">Success:</span>';
        echo \yii::t('manager', 'config_finished');
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
    } catch (\Exception $e) {
        echo '<div class="alert alert-danger" role="alert">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">Error:</span>
                ' . nl2br(Html::encode($e->getMessage())) . '
            </div>';
    }
} else {
    echo '<div class="alert alert-info" role="alert">
        <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
        <span class="sr-only">Welcome:</span>
        ' . \yii::t('manager', 'welcome') . '
    </div>';
}

echo '</div>';

echo Html::endForm();


echo Html::beginForm('', 'post', ['class' => 'antragsgruenInitForm form-horizontal fuelux']);

echo '<h2 class="green">' . 'Datenbank' . '</h2>';
echo '<div class="content">';

echo '<div class="form-group sqlType">
    <label class="col-sm-4 control-label" for="sqlType">' . 'Datenbank-Typ' . ':</label>
    <div class="col-sm-8">';
echo HTMLTools::fueluxSelectbox(
    'sqlType',
    [
        'mysql' => 'MySQL / MariaDB',
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
        <input type="password" name="sqlPassword" value="" class="form-control" id="sqlPassword"';
if ($form->sqlPassword != '') {
    echo ' placeholder="' . 'Unverändert lassen' . '"';
}
echo '>
        <label style="font-weight: normal; font-size: 0.9em;">
            <input type="checkbox" name="sqlPasswordNone" value="" id="sqlPasswordNone">
            Kein Passwort
        </label>
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
    echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="adminUsername">' . 'Benutzername (E-Mail)' . ':</label>
    <div class="col-sm-8">
        <input type="email" required name="adminUsername"
        value="' . Html::encode($form->adminUsername) . '" class="form-control" id="adminUsername">
    </div>
</div>';

    echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="adminPassword">' . 'Passwort' . ':</label>
    <div class="col-sm-8">
        <input type="password" required name="adminPassword" value="" class="form-control" id="adminPassword">
    </div>
</div>';
}

echo '</div>';


echo '<div class="content saveholder">
<button type="submit" name="saveDb" class="btn btn-primary">Speichern</button>
</div>';


echo Html::endForm();
