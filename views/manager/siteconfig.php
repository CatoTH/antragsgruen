<?php

use app\components\HTMLTools;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\settings\AntragsgruenApp $config
 * @var bool $editable
 * @var string $makeEditabeCommand
 */


/** @var \app\controllers\ManagerController $controller */
$controller  = $this->context;
$this->title = 'Antragsgrün einrichten';
$layout      = $controller->layoutParams;
$layout->loadFuelux();
$layout->addJS('js/manager.js');
$layout->addOnLoadJS('$.SiteManager.siteConfig();');


echo '<h1>' . 'Antragsgrün einrichten' . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'siteConfigForm form-horizontal fuelux']);


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

echo '</div>';


echo '<h2 class="green">' . 'E-Mail-Einstellungen' . '</h2>';

echo '<div class="content">';
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

$currTransport = (isset($config->mailService['transport']) ? $config->mailService['transport'] : '');
echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="emailTransport">' . 'Versandart' . ':</label>
    <div class="col-sm-8">';
echo HTMLTools::fueluxSelectbox(
    'mailService[transport]',
    [
        'sendmail' => 'Sendmail (lokal)',
        'smtp'     => 'SMTP (externer Mailserver)',
        'mandrill' => 'Mandrill',
    ],
    $currTransport,
    ['id' => 'emailTransport']
);
echo '</div>
</div>';

$currApiKey = (isset($config->mailService['apiKey']) ? $config->mailService['apiKey'] : '');
echo '<div class="form-group emailOption mandrillApiKey">
    <label class="col-sm-4 control-label" for="mandrillApiKey">' . 'Mandrill\'s API-Key' . ':</label>
    <div class="col-sm-8">
        <input type="text" name="mailService[mandrillApiKey]" placeholder=""
        value="' . Html::encode($currApiKey) . '" class="form-control" id="mandrillApiKey">
    </div>
</div>';


$currHost = (isset($config->mailService['host']) ? $config->mailService['host'] : '');
echo '<div class="form-group emailOption smtpHost">
    <label class="col-sm-4 control-label" for="smtpHost">' . 'SMTP Server' . ':</label>
    <div class="col-sm-8">
        <input type="text" name="mailService[smtpHost]" placeholder="smtp.yourserver.de"
        value="' . Html::encode($currHost) . '" class="form-control" id="smtpHost">
    </div>
</div>';

$currPort = (isset($config->mailService['port']) ? $config->mailService['port'] : 25);
echo '<div class="form-group emailOption smtpPort">
    <label class="col-sm-4 control-label" for="smtpPort">' . 'SMTP Port' . ':</label>
    <div class="col-sm-3">
        <input type="number" name="mailService[smtpPort]" placeholder="25"
        value="' . Html::encode($currPort) . '" class="form-control" id="smtpPort">
    </div>
</div>';

$currAuthType = (isset($config->mailService['authType']) ? $config->mailService['authType'] : '');
echo '<div class="form-group emailOption smtpAuthType">
    <label class="col-sm-4 control-label" for="smtpAuthType">' . 'SMTP Login-Typ' . ':</label>
    <div class="col-sm-8">';
echo HTMLTools::fueluxSelectbox(
    'mailService[smtpAuthType]',
    [
        'none'      => 'Kein Login',
        'plain'     => 'Plain',
        'login'     => 'LOGIN',
        'crammd5'   => 'Cram-MD5',
        'plain_tls' => 'PLAIN / TLS',
    ],
    $currAuthType,
    ['id' => 'smtpAuthType']
);
echo '</div>
</div>';

$currUsername = (isset($config->mailService['username']) ? $config->mailService['username'] : '');
echo '<div class="form-group emailOption smtpUsername">
    <label class="col-sm-4 control-label" for="smtpUsername">' . 'SMTP BenutzerInnenname' . ':</label>
    <div class="col-sm-8">
        <input type="text" name="mailService[smtpUsername]" placeholder=""
        value="' . Html::encode($currUsername) . '" class="form-control" id="smtpUsername">
    </div>
</div>';

$currPassword = (isset($config->mailService['password']) ? $config->mailService['password'] : '');
echo '<div class="form-group emailOption smtpPassword">
    <label class="col-sm-4 control-label" for="smtpPassword">' . 'SMTP Passwort' . ':</label>
    <div class="col-sm-8">
        <input type="password" name="mailService[smtpPassword]" placeholder=""
        value="' . Html::encode($currPassword) . '" class="form-control" id="smtpPassword">
    </div>
</div>';


echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary" ';
if (!$editable) {
    echo 'disabled';
}
echo '>Speichern</button>
</div>';

echo '</div>';

echo Html::endForm();
