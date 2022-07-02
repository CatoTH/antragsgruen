<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var string $backUrl
 */

$params = \app\models\settings\AntragsgruenApp::getInstance();

?>
<section class="loginSimplesaml">
<h2 class="green">&quot;Grüne Les Vert-E-S&quot;-Login</h2>
    <div class="content row">
    <?php
    $action = $params->domainPlain . 'loginsaml';
    echo Html::beginForm($action, 'post', ['class' => 'col-sm-4', 'id' => 'gruenechLoginForm']);

    $absoluteBack = UrlHelper::absolutizeLink($backUrl);
    ?>
    <input type="hidden" name="backUrl" value="<?= Html::encode($absoluteBack) ?>">
    <button type="submit" class="btn btn-primary" name="samlLogin">
        <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span> Grüne / Les Vert-E-S: Login
    </button>

    <?php
    echo Html::endForm()
    ?>
    <div id="loginSamlHint">
        <strong>Hinweis:</strong> Hier wirst du auf eine Seite unter „https://saml.gruene.de/” umgeleitet,
        die vom Bundesverband betrieben wird.<br>Dort musst du dein Benutzer*innenname/Passwort des Grünen Netzes
        eingeben. Dein Passwort bleibt dabei geheim und wird <i>nicht</i> an Antragsgrün übermittelt.
        <br><br>
        <strong>Zugangsdaten vergessen?</strong> Klicke auf „Einloggen” und auf der folgenden Seite auf „Passwort vergessen?”.
    </div>
</section>
