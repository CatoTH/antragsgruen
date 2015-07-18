<?php

use app\components\UrlHelper;
use app\models\forms\LoginUsernamePasswordForm;
use yii\helpers\Html;
use app\models\settings\Site as SiteSettings;

/**
 * @var yii\web\View $this
 * @var LoginUsernamePasswordForm $usernamePasswordForm
 * @var string $msg_err
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Login';
$layout->addBreadcrumb('Login');

if ($controller->site) {
    $loginMethods = $controller->site->getSettings()->loginMethods;
} else {
    $loginMethods = SiteSettings::$SITE_MANAGER_LOGIN_METHODS;
}
/** @var \app\models\settings\AntragsgruenApp $params */
$params = \Yii::$app->params;

echo '<h1>Login</h1>';


if (in_array(SiteSettings::LOGIN_STD, $loginMethods) || in_array(SiteSettings::LOGIN_NAMESPACED, $loginMethods)) {
    $pwMinLen = \app\models\forms\LoginUsernamePasswordForm::PASSWORD_MIN_LEN;

    echo '<section class="loginUsername">
    <h2 class="green">Login per Benutzer_Innenname / Passwort</h2>
    <div class="content row">';

    if ($usernamePasswordForm->error != "") {
        echo '<div class="alert alert-danger" role="alert">
  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
  <span class="sr-only">Error:</span>';
        echo Html::encode($usernamePasswordForm->error);
        echo '</div>';
    }

    echo Html::beginForm('', 'post', ['class' => 'col-sm-6', 'id' => 'usernamePasswordForm']);

    $preUsername = $usernamePasswordForm->username;
    $preName     = $usernamePasswordForm->name;

    if (in_array(SiteSettings::LOGIN_STD, $loginMethods)) {
        $pre_checked = (isset($_REQUEST["createAccount"]) ? 'checked' : '');
        echo '<div class="checkbox"><label>
            <input type="checkbox" name="createAccount" id="createAccount" ' . $pre_checked . '>
            Neuen Zugang anlegen
            </label></div>';
    } else {
        echo '<div class="alert alert-info">!';
        // @TODO
        //echo veranstaltungsspezifisch_hinweis_namespaced_accounts($this->veranstaltung,
        //'<strong>Hinweis:</strong> wenn du berechtigt bist, (Änderungs-)Anträge einzustellen,
        //solltest du die Zugangsdaten per E-Mail erhalten haben.<br>
        //Falls du keine bekommen haben solltest, melde dich bitte bei den
        //Organisatoren dieses Parteitags / dieser Programmdiskussion.');
        echo "</div>";
    }

    echo '<div class="form-group">
        <label for="username">E-Mail-Adresse / BenutzerInnenname:</label>
            <input class="form-control" name="username" id="username" type="text" autofocus required
            placeholder="E-Mail-Adresse" value="' . Html::encode($preUsername) . '">
        </div>

        <div class="form-group">
            <label for="passwordInput">Passwort:</label>
            <input type="password" name="password" id="passwordInput" required class="form-control"
            data-min-len="' . $pwMinLen . '">
        </div>

        <div class="form-group hidden"  id="pwdConfirm">
            <label for="passwordConfirm">Passwort (Bestätigung):</label>
            <input type="password" name="passwordConfirm" id="passwordConfirm" class="form-control">
        </div>

        <div class="form-group hidden" id="regName">
            <label for="name">Dein Name:</label>
            <input type="text" value="' . Html::encode($preName) . '" name="name" id="name" class="form-control">
        </div>

    <div class="row">
        <div class="col-md-6">
            <button type="submit" class="btn btn-primary" name="loginusernamepassword">
                <span id="loginStr"><span class="glyphicon glyphicon-log-in"></span> Einloggen</span>
                <span id="createStr"><span class="glyphicon glyphicon-plus-sign"></span> Anlegen</span>
            </button>
        </div>
        <div class="col-md-6 passwordRecovery">
            ' . Html::a('Passwort vergessen?', UrlHelper::createUrl('user/recovery')) . '
        </div>
    </div>';
    echo Html::endForm();

    echo '</div>
    </section>';

}

if ($params->hasWurzelwerk) {
    $hide_ww_login = !in_array(SiteSettings::LOGIN_WURZELWERK, $loginMethods);
    echo '<section class="loginWurzelwerk">';
    if ($hide_ww_login) {
        echo '<div class="content">
        <a href="#" onClick="$(\'#admin_login_www\').toggleClass(\'hidden\'); return false;">Admin-Login</a>
    </div>
    <div id="admin_login_www" class="hidden">';
    }

    echo '<h2 class="green">Wurzelwerk-Login</h2>
    <div class="content row">';

    $backUrl = UrlHelper::createUrl('consultation/index');
    $action  = UrlHelper::createUrl(['user/loginwurzelwerk', 'backUrl' => $backUrl]);
    echo Html::beginForm($action, 'post', ['class' => 'col-sm-4', 'id' => 'wurzelwerkLoginForm']);

    echo '<div class="form-group">
    <label for="wurzelwerkAccount">WurzelWerk-Account:</label>
  <input name="username" id="wurzelwerkAccount" type="text" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary" name="wurzelwerkLogin">
            <span class="glyphicon glyphicon-log-in"></span> Einloggen
    </button>
';
    echo Html::endForm();
    echo '<div id="loginWurzelwerkHint">
    <strong>Hinweis:</strong> Hier wirst du auf eine Seite unter "https://service.gruene.de/" umgeleitet,
    die vom Bundesverband betrieben wird.<br>Dort musst du dein Wurzelwerk-BenutzerInnenname/Passwort
    eingeben und bestätigen, dass deine E-Mail-Adresse an Antragsgrün übermittelt wird.
    Dein Wurzelwerk-Passwort bleibt geheim und wird <i>nicht</i> an Antragsgrün übermittelt.
    <br><br>
    <a href="https://netz.gruene.de/passwordForgotten.form" class="loginWurzelwerkForgot" target="_blank">
        Wurzelwerk-Zugangsdaten vergessen?
    </a>
        </div>
</div>';

    if ($hide_ww_login) {
        echo '</div>';
    }
    echo '</section>';
}


if (in_array(SiteSettings::LOGIN_EXTERNAL, $loginMethods)) {
    echo '<section class="loginOpenID">
    <h2 class="green">OpenID-Login</h2>
	<div class="content row">';
    echo Html::beginForm('', 'post', ['class' => 'col-sm-6']);

    echo '<div class="form-group">
        <label for="openidUrl">OpenID-URL</label>
        <input class="form-control" name="OAuthLoginForm[openIdUrl]"
            id="openidUrl" type="text" placeholder="https://...">
      </div>

	  <button type="submit" class="btn btn-primary">
        <span class="glyphicon glyphicon-log-in"></span> Einloggen
      </button>
    ';

    echo Html::endForm();
    echo '</div></section>';
}

$layout->addOnLoadJS('$.Antragsgruen.loginForm();');
