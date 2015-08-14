<?php

use app\components\HTMLTools;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\settings\AntragsgruenApp $config
 * @var bool $editable
 */


/** @var \app\controllers\ManagerController $controller */
$controller  = $this->context;
$this->title = 'Antragsgrün einrichten';
$layout      = $controller->layoutParams;
$layout->loadFuelux();

echo '<h1>' . 'Antragsgrün einrichten' . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'content siteConfigForm form-horizontal']);

echo $controller->showErrors();

if (!$editable) {
    echo '<div class="alert alert-danger" role="alert">';
    echo 'Die Einstellungen können nicht bearbeitet werden, da die Datei config/config.php nicht bearbeitbar ist.
    <br>Das lässt sich mit folgendem Befehl (oder ähnlich, je nach Betriebssystem) auf der Kommandozeile beheben:
    <br><pre>';
    echo 'chmod a+r @TODO'; // @TODO
    echo '</pre>';
    echo '</div>';
}

echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="baseLanguage">' . 'Sprache' . ':</label>
    <div class="col-sm-8">';
$languages = \app\components\MessageSource::getBaseLanguages();
echo HTMLTools::fueluxSelectbox('baseLanguage', $languages, $config->baseLanguage, ['id' => 'baseLanguage']);
echo '</div>
</div>';


echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="resourceBase">' . 'Standard-Verzeichnis' . ':</label>
    <div class="col-sm-8">
        <input type="text" required name="resourceBase" placeholder="/"
        value="' . Html::encode($config->resourceBase) . '" class="form-control" id="resourceBase">
    </div>
</div>';

echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="tmpDir">' . 'Temporäres Verzeichis' . ':</label>
    <div class="col-sm-8">
        <input type="text" required name="tmpDir" placeholder="/tmp/"
        value="' . Html::encode($config->tmpDir) . '" class="form-control" id="tmpDir">
    </div>
</div>';

echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="xelatexPath">' . 'Ort von xelatex' . ':</label>
    <div class="col-sm-8">
        <input type="text" name="xelatexPath" placeholder="/usr/bin/xelatex"
        value="' . Html::encode($config->xelatexPath) . '" class="form-control" id="xelatexPath">
    </div>
</div>';

echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="xdvipdfmx">' . 'Ort von xdvipdfmx' . ':</label>
    <div class="col-sm-8">
        <input type="text" name="xdvipdfmx" placeholder="/usr/bin/xdvipdfmx"
        value="' . Html::encode($config->xdvipdfmx) . '" class="form-control" id="xdvipdfmx">
    </div>
</div>';

echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="mailFromEmail">' . 'E-Mail-Absender - Adresse' . ':</label>
    <div class="col-sm-8">
        <input type="text" name="mailFromEmail" placeholder="antragsgruen@example.org"
        value="' . Html::encode($config->mailFromEmail) . '" class="form-control" id="mailFromEmail">
    </div>
</div>';

echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="mailFromName">' . 'E-Mail-Absender - Name' . ':</label>
    <div class="col-sm-8">
        <input type="text" name="mailFromName" placeholder="Antragsgrün"
        value="' . Html::encode($config->mailFromName) . '" class="form-control" id="mailFromName">
    </div>
</div>';


echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary" ';
if (!$editable) {
    echo 'disabled';
}
echo '>Speichern</button>
</div>';

var_dump($config);

echo Html::endForm();
