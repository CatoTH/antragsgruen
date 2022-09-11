<?php

use app\components\UrlHelper;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

/**
 * @var bool $loginActive
 * @var string $backUrl
 */

echo '<section class="loginSimplesaml">';
if (!$loginActive) {
    echo '<div id="admin_login_saml" class="hidden">';
}

echo '<h2 class="green">„Grünes Netz”-Login</h2>
    <div class="content row">';

$action = AntragsgruenApp::getInstance()->domainPlain . 'gruene-login';
echo Html::beginForm($action, 'post', ['class' => 'col-sm-4', 'id' => 'samlLoginForm']);

$absoluteBack = UrlHelper::absolutizeLink($backUrl);
echo '
        <input type="hidden" name="backUrl" value="' . Html::encode($absoluteBack) . '">
        <button type="submit" class="btn btn-primary" name="samlLogin">
            <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span> Grünes Netz: Login
    </button>';

echo Html::endForm();
echo '<div id="loginSamlHint">
    <strong>Hinweis:</strong> Hier wirst du auf eine Seite unter „https://saml.gruene.de/” umgeleitet,
    die vom Bundesverband betrieben wird.<br>Dort musst du dein Benutzer*innenname/Passwort des Grünen Netzes
    eingeben. Dein Passwort bleibt dabei geheim und wird <i>nicht</i> an Antragsgrün übermittelt.
    <br><br>
    <strong>Zugangsdaten vergessen?</strong> Klicke auf „Einloggen” und auf der folgenden Seite auf „Passwort vergessen?”.
        </div>
</div>';

if (!$loginActive) {
    echo '</div>';
}
echo '</section>';
