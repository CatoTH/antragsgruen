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
$this->title = \yii::t('manager', 'title_install');
$layout      = $controller->layoutParams;
$layout->loadFuelux();


echo '<h1>' . \yii::t('manager', 'title_install') . '</h1>';
echo Html::beginForm('', 'post', [
    'class'                    => 'siteConfigForm form-horizontal fuelux',
    'data-antragsgruen-widget' => 'manager/SiteConfig',
]);


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

echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="baseLanguage">' . \yii::t('manager', 'language') . ':</label>
    <div class="col-sm-8">';
$languages = \app\components\MessageSource::getBaseLanguages();
echo HTMLTools::fueluxSelectbox('baseLanguage', $languages, $config->baseLanguage, ['id' => 'baseLanguage']);
echo '</div>
</div>';


echo '<div class="form-group">
  <label class="col-sm-4 control-label" for="resourceBase">' . \yii::t('manager', 'default_dir') . ':</label>
  <div class="col-sm-8">
    <input type="text" required name="resourceBase" placeholder="/"
      value="' . Html::encode($config->resourceBase) . '" class="form-control" id="resourceBase">
  </div>
</div>';

echo '<div class="form-group">
  <label class="col-sm-4 control-label" for="tmpDir">' . \yii::t('manager', 'tmp_dir') . ':</label>
  <div class="col-sm-8">
    <input type="text" required name="tmpDir" placeholder="/tmp/"
      value="' . Html::encode($config->tmpDir) . '" class="form-control" id="tmpDir">
  </div>
</div>';

echo '<div class="form-group">
  <label class="col-sm-4 control-label" for="xelatexPath">' . \yii::t('manager', 'path_xelatex') . ':</label>
  <div class="col-sm-8">
    <input type="text" name="xelatexPath" placeholder="/usr/bin/xelatex"
      value="' . Html::encode($config->xelatexPath) . '" class="form-control" id="xelatexPath">
  </div>
</div>';

echo '<div class="form-group">
  <label class="col-sm-4 control-label" for="xdvipdfmx">' . \yii::t('manager', 'path_xdvipdfmx') . ':</label>
  <div class="col-sm-8">
    <input type="text" name="xdvipdfmx" placeholder="/usr/bin/xdvipdfmx"
      value="' . Html::encode($config->xdvipdfmx) . '" class="form-control" id="xdvipdfmx">
  </div>
</div>';

echo '</div>';


echo '<h2 class="green">' . \yii::t('manager', 'email_settings') . '</h2>';

echo '<div class="content">';
echo '<div class="form-group">
  <label class="col-sm-4 control-label" for="mailFromEmail">' .
    \yii::t('manager', 'email_from_address') . ':</label>
  <div class="col-sm-8">
    <input type="text" name="mailFromEmail" placeholder="antragsgruen@example.org"
      value="' . Html::encode($config->mailFromEmail) . '" class="form-control" id="mailFromEmail">
  </div>
</div>';

echo '<div class="form-group">
  <label class="col-sm-4 control-label" for="mailFromName">' . \yii::t('manager', 'email_from_name') . ':</label>
  <div class="col-sm-8">
    <input type="text" name="mailFromName" placeholder="Antragsgrün"
      value="' . Html::encode($config->mailFromName) . '" class="form-control" id="mailFromName">
  </div>
</div>';

$currTransport = (isset($config->mailService['transport']) ? $config->mailService['transport'] : '');
echo '<div class="form-group">
  <label class="col-sm-4 control-label" for="emailTransport">' . \yii::t('manager', 'email_transport') . ':</label>
  <div class="col-sm-8">';
echo HTMLTools::fueluxSelectbox(
    'mailService[transport]',
    [
        'sendmail' => \yii::t('manager', 'email_sendmail'),
        'smtp'     => \yii::t('manager', 'email_smtp'),
        'mailgun'  => \yii::t('manager', 'email_mailgun'),
        'mandrill' => \yii::t('manager', 'email_mandrill'),
        'none'     => \yii::t('manager', 'email_none'),
    ],
    $currTransport,
    ['id' => 'emailTransport']
);
echo '</div>
</div>';

$currApiKey = (isset($config->mailService['apiKey']) ? $config->mailService['apiKey'] : '');
echo '<div class="form-group emailOption mandrillApiKey">
  <label class="col-sm-4 control-label" for="mandrillApiKey">' . \yii::t('manager', 'mandrill_api') . ':</label>
  <div class="col-sm-8">
    <input type="text" name="mailService[mandrillApiKey]" placeholder=""
      value="' . Html::encode($currApiKey) . '" class="form-control" id="mandrillApiKey">
  </div>
</div>';


$currApiKey = (isset($config->mailService['apiKey']) ? $config->mailService['apiKey'] : '');
echo '<div class="form-group emailOption mailgunApiKey">
  <label class="col-sm-4 control-label" for="mailgunApiKey">' . \yii::t('manager', 'mailgun_api') . ':</label>
  <div class="col-sm-8">
    <input type="text" name="mailService[mailgunApiKey]" placeholder=""
      value="' . Html::encode($currApiKey) . '" class="form-control" id="mailgunApiKey">
  </div>
</div>';


$currDomain = (isset($config->mailService['domain']) ? $config->mailService['domain'] : '');
echo '<div class="form-group emailOption mailgunDomain">
  <label class="col-sm-4 control-label" for="mailgunDomain">' . \yii::t('manager', 'mailgun_domain') . ':</label>
  <div class="col-sm-8">
    <input type="text" name="mailService[mailgunDomain]" placeholder=""
      value="' . Html::encode($currDomain) . '" class="form-control" id="mailgunDomain">
  </div>
</div>';


$currHost = (isset($config->mailService['host']) ? $config->mailService['host'] : '');
echo '<div class="form-group emailOption smtpHost">
    <label class="col-sm-4 control-label" for="smtpHost">' . \yii::t('manager', 'smtp_server') . ':</label>
    <div class="col-sm-8">
        <input type="text" name="mailService[smtpHost]" placeholder="smtp.yourserver.de"
        value="' . Html::encode($currHost) . '" class="form-control" id="smtpHost">
    </div>
</div>';

$currPort = (isset($config->mailService['port']) ? $config->mailService['port'] : 25);
echo '<div class="form-group emailOption smtpPort">
    <label class="col-sm-4 control-label" for="smtpPort">' . \yii::t('manager', 'smtp_port') . ':</label>
    <div class="col-sm-3">
        <input type="number" name="mailService[smtpPort]" placeholder="25"
        value="' . Html::encode($currPort) . '" class="form-control" id="smtpPort">
    </div>
</div>';

$currAuthType = (isset($config->mailService['authType']) ? $config->mailService['authType'] : '');
echo '<div class="form-group emailOption smtpAuthType">
    <label class="col-sm-4 control-label" for="smtpAuthType">' . \yii::t('manager', 'smtp_login') . ':</label>
    <div class="col-sm-8">';
echo HTMLTools::fueluxSelectbox(
    'mailService[smtpAuthType]',
    [
        'none'      => \yii::t('manager', 'smtp_login_none'),
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
  <label class="col-sm-4 control-label" for="smtpUsername">' .
    \yii::t('manager', 'smtp_username') . ':</label>
  <div class="col-sm-8">
    <input type="text" name="mailService[smtpUsername]" placeholder=""
      value="' . Html::encode($currUsername) . '" class="form-control" id="smtpUsername">
  </div>
</div>';

$currPassword = (isset($config->mailService['password']) ? $config->mailService['password'] : '');
echo '<div class="form-group emailOption smtpPassword">
  <label class="col-sm-4 control-label" for="smtpPassword">' . \yii::t('manager', 'smtp_password') . ':</label>
  <div class="col-sm-8">
    <input type="password" name="mailService[smtpPassword]" placeholder=""
      value="' . Html::encode($currPassword) . '" class="form-control" id="smtpPassword">
  </div>
</div>';

echo '<div class="form-group"><label>';
echo Html::checkbox(
    'confirmEmailAddresses',
    $config->confirmEmailAddresses,
    ['id' => 'confirmEmailAddresses']
) . ' ';
echo \Yii::t('manager', 'confirm_email_addresses') . '</label></div>';


echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary" ';
if (!$editable) {
    echo 'disabled';
}
echo '>' . \yii::t('manager', 'save') . '</button>
</div>';

echo '</div>';

echo Html::endForm();
