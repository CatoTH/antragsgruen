<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\settings\AntragsgruenApp $config
 * @var bool $editable
 * @var string $makeEditabeCommand
 */


/** @var \app\controllers\ManagerController $controller */
$controller  = $this->context;
$this->title = Yii::t('manager', 'title_install');
$layout      = $controller->layoutParams;


echo '<h1>' . Yii::t('manager', 'title_install') . '</h1>';
echo Html::beginForm('', 'post', [
    'class'                    => 'siteConfigForm form-horizontal',
    'data-antragsgruen-widget' => 'manager/SiteConfig',
]);


echo '<div class="content">';
echo $controller->showErrors();

if (!$editable) {
    echo '<div class="alert alert-danger">';
    echo Yii::t('manager', 'err_settings_ro');
    echo '<br><br><pre>';
    echo Html::encode($makeEditabeCommand);
    echo '</pre>';
    echo '</div>';
}

echo '<div class="stdTwoCols">
    <label class="leftColumn" for="baseLanguage">' . Yii::t('manager', 'language') . ':</label>
    <div class="rightColumn">';
$languages = \app\components\yii\MessageSource::getBaseLanguages();
echo Html::dropDownList('baseLanguage', $config->baseLanguage, $languages, ['id' => 'baseLanguage', 'class' => 'stdDropdown']);
echo '</div>
</div>';


echo '<div class="stdTwoCols">
  <label class="leftColumn" for="resourceBase">' . Yii::t('manager', 'default_dir') . ':</label>
  <div class="rightColumn">
    <input type="text" required name="resourceBase" placeholder="/"
      value="' . Html::encode($config->resourceBase) . '" class="form-control" id="resourceBase">
  </div>
</div>';

echo '<div class="stdTwoCols">
  <label class="leftColumn" for="lualatexPath">' . Yii::t('manager', 'path_lualatex') . ':</label>
  <div class="rightColumn">
    <input type="text" name="lualatexPath" placeholder="/usr/bin/lualatex"
      value="' . Html::encode($config->lualatexPath) . '" class="form-control" id="lualatexPath">
  </div>
</div>';

echo '</div>';


echo '<h2 class="green">' . Yii::t('manager', 'email_settings') . '</h2>';

echo '<div class="content">';
echo '<div class="stdTwoCols">
  <label class="leftColumn" for="mailFromEmail">' .
    Yii::t('manager', 'email_from_address') . ':</label>
  <div class="rightColumn">
    <input type="text" name="mailFromEmail" placeholder="antragsgruen@example.org"
      value="' . Html::encode($config->mailFromEmail) . '" class="form-control" id="mailFromEmail">
  </div>
</div>';

echo '<div class="stdTwoCols">
  <label class="leftColumn" for="mailFromName">' . Yii::t('manager', 'email_from_name') . ':</label>
  <div class="rightColumn">
    <input type="text" name="mailFromName" placeholder="Antragsgrün"
      value="' . Html::encode($config->mailFromName) . '" class="form-control" id="mailFromName">
  </div>
</div>';

$currTransport = ($config->mailService['transport'] ?? '');
echo '<div class="stdTwoCols">
  <label class="leftColumn" for="emailTransport">' . Yii::t('manager', 'email_transport') . ':</label>
  <div class="rightColumn">';
echo Html::dropDownList(
    'mailService[transport]',
    $currTransport,
    [
        'sendmail' => Yii::t('manager', 'email_sendmail'),
        'smtp'     => Yii::t('manager', 'email_smtp'),
        'mailjet'  => Yii::t('manager', 'email_mailjet'),
        //'mailgun'  => Yii::t('manager', 'email_mailgun'),
        //'mandrill' => Yii::t('manager', 'email_mandrill'),
        'none'     => Yii::t('manager', 'email_none'),
    ],
    ['id' => 'emailTransport', 'class' => 'stdDropdown']
);
echo '</div>
</div>';

$currApiKey        = ($config->mailService['apiKey'] ?? '');
$currMailjetSecret = ($config->mailService['mailjetApiSecret'] ?? '');
$currDomain        = ($config->mailService['domain'] ?? '');

$currHost     = ($config->mailService['host'] ?? '');
$currPort     = ($config->mailService['port'] ?? 25);
$currAuthType = ($config->mailService['authType'] ?? '');
$currUsername = ($config->mailService['username'] ?? '');
$currPassword = ($config->mailService['password'] ?? '');
$currTls      = (isset($config->mailService['encryption']) && $config->mailService['encryption'] === 'tls');

?>
    <!-- Mailjet -->
    <div class="stdTwoCols emailOption mailjetApiKey">
        <label class="leftColumn" for="mailjetApiKey"><?= Yii::t('manager', 'mailjet_api_key') ?></label>
        <div class="rightColumn">
            <input type="text" name="mailService[mailjetApiKey]" placeholder=""
                   value="<?= Html::encode($currApiKey) ?>" class="form-control" id="mailjetApiKey">
        </div>
    </div>
    <div class="stdTwoCols emailOption mailjetApiSecret">
        <label class="leftColumn" for="mailjetApiSecret"><?= Yii::t('manager', 'mailjet_secret') ?>:</label>
        <div class="rightColumn">
            <input type="text" name="mailService[mailjetApiSecret]" placeholder=""
                   value="<?= Html::encode($currMailjetSecret) ?>" class="form-control" id="mailjetApiSecret">
        </div>
    </div>

    <!-- SMTP -->
    <div class="stdTwoCols emailOption smtpHost">
        <label class="leftColumn" for="smtpHost"><?= Yii::t('manager', 'smtp_server') ?>:</label>
        <div class="rightColumn">
            <input type="text" name="mailService[smtpHost]" placeholder="smtp.yourserver.de"
                   value="<?= Html::encode($currHost) ?>" class="form-control" id="smtpHost">
        </div>
    </div>
    <div class="stdTwoCols emailOption smtpPort">
        <label class="leftColumn" for="smtpPort"><?= Yii::t('manager', 'smtp_port') ?>:</label>
        <div class="rightColumn">
            <input type="number" name="mailService[smtpPort]" placeholder="25"
                   value="<?= Html::encode($currPort) ?>" class="form-control" id="smtpPort">
        </div>
    </div>
    <div class="stdTwoCols emailOption smtpTls">
        <label class="leftColumn" for="smtpTls"><?= Yii::t('manager', 'smtp_tls') ?>:</label>
        <div class="rightColumn">
            <?php
            echo Html::checkbox('mailService[smtpTls]', $currTls, ['id' => 'smtpTls']);
            ?>
        </div>
    </div>
    <div class="stdTwoCols emailOption smtpAuthType">
        <label class="leftColumn" for="smtpAuthType"><?= Yii::t('manager', 'smtp_login') ?>:</label>
        <div class="rightColumn"><?php
            echo Html::dropDownList(
                'mailService[smtpAuthType]',
                $currAuthType,
                [
                    'none'      => Yii::t('manager', 'smtp_login_none'),
                    'plain'     => 'Plain',
                    'login'     => 'LOGIN',
                    'crammd5'   => 'Cram-MD5',
                    'plain_tls' => 'PLAIN / TLS',
                ],
                ['id' => 'smtpAuthType', 'class' => 'stdDropdown']
            );
            ?>
        </div>
    </div>
    <div class="stdTwoCols emailOption smtpUsername">
        <label class="leftColumn" for="smtpUsername"><?= Yii::t('manager', 'smtp_username') ?>:</label>
        <div class="rightColumn">
            <input type="text" name="mailService[smtpUsername]" placeholder=""
                   value="<?= Html::encode($currUsername) ?>" class="form-control" id="smtpUsername">
        </div>
    </div>

    <div class="stdTwoCols emailOption smtpPassword">
        <label class="leftColumn" for="smtpPassword"><?= Yii::t('manager', 'smtp_password') ?>:</label>
        <div class="rightColumn">
            <input type="password" name="mailService[smtpPassword]" placeholder=""
                   value="<?= Html::encode($currPassword) ?>" class="form-control" id="smtpPassword">
        </div>
    </div>

<?php

echo '<br><label>';
echo Html::checkbox('confirmEmailAddresses', $config->confirmEmailAddresses, ['id' => 'confirmEmailAddresses']) . ' ';
echo Yii::t('manager', 'confirm_email_addresses') . '</label><br><br>';


echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary" ';
if (!$editable) {
    echo 'disabled';
}
echo '>' . Yii::t('manager', 'save') . '</button>
</div>';

echo '</div>';

echo Html::endForm();
