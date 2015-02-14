<?php

/**
 * @var $this yii\web\View
 * @var string $msg_err
 */
use yii\helpers\Html;

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$wording    = $controller->consultation->getWording();
$layout     = $controller->layoutParams;


$layout->breadcrumbs = ['Login'];


if ($msg_err != "") {
    echo '<h1>Fehler</h1>
    <div class="content"><div class="alert alert-error">';
    echo $msg_err;
    echo '</div></div>';
}

echo '<h1>Login</h1>';


if (!$controller->site || !$controller->site->getSettings()->onlyWurzelwerk) {
    echo '<h2>Login per BenutzerInnenname / Passwort</h2>
    <div class="content">';

    echo Html::beginForm('', 'post', ['class' => 'col-sm-5']);

    $pre_username = (isset($_REQUEST["username"]) ? $_REQUEST["username"] : '');
    $pre_name     = (isset($_REQUEST["name"]) ? $_REQUEST["name"] : '');

    if ($controller->site->getSettings()->onlyNamespacedAccounts) {
        echo '<div class="alert alert-info">!';
        // @TODO
        //echo veranstaltungsspezifisch_hinweis_namespaced_accounts($this->veranstaltung,
        //'<strong>Hinweis:</strong> wenn du berechtigt bist, (Änderungs-)Anträge einzustellen,
        //solltest du die Zugangsdaten per E-Mail erhalten haben.<br>
        //Falls du keine bekommen haben solltest, melde dich bitte bei den
        //Organisatoren dieses Parteitags / dieser Programmdiskussion.');
        echo "</div>";
    } else {
        $pre_checked = (isset($_REQUEST["neuer_account"]) ? 'checked' : '');
        echo '<div class="checkbox"><label>
            <input type="checkbox" name="neuer_account" id="neuer_account_check" ' . $pre_checked . '>
            Neuen Zugang anlegen
            </label></div>';
    }

    echo '<div class="form-group">
        <label for="username">E-Mail-Adresse / BenutzerInnenname:</label>
            <input class="form-control" name="username" id="username" type="text" autofocus required
            placeholder="E-Mail-Adresse" value="' . Html::encode($pre_username) . '">
        </div>

        <div class="form-group">
            <label for="password_input">Passwort:</label>
            <input type="password" name="password" id="password_input" required class="form-control">
        </div>

        <div class="form-group"  id="pwd_confirm" style="display: none;">
            <label for="password_confirm">Passwort (Bestätigung):</label>>
            <input type="password" name="password_confirm" id="password_confirm" class="form-control">
        </div>

        <div class="form-group" id="reg_name" style="display: none;">
            <label for="name">Dein Name:</label>
            <input type="text" value="' . Html::encode($pre_name) . '" name="name" id="name">
        </div>

        <script>
            $(function () {
                $("#neuer_account_check").change(function () {
                    if ($(this).prop("checked")) {
                        $("#pwd_confirm").show();
                        $("#reg_name").show().find("input").attr("required", "required");
                        $("#password_input").attr("placeholder", "Min. 6 Zeichen");
                    } else {
                        $("#pwd_confirm").hide();
                        $("#reg_name").hide().find("input").removeAttr("required");
                        $("#password_input").attr("placeholder", "");
                    }
                }).trigger("change");
            })
        </script>
        <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-log-in"></span> Einloggen</button>
        ';
    echo Html::endForm();

    echo '</div>';
}

$hide_ww_login = ($controller->site && $controller->site->getSettings()->onlyNamespacedAccounts);
if ($hide_ww_login) {
    echo '<div class="content">
        <a href="#" onClick="$(\'#admin_login_www\').toggle(); return false;">Admin-Login</a>
    </div>
    <div id="admin_login_www" style="display: none;">';
}


echo '<h2>Wurzelwerk-Login</h2>
    <div class="content">';
echo Html::beginForm('', 'post', ['class' => 'col-sm-5']);

echo '<div class="form-group">
    <label for="wurzelwerkAccount">WurzelWerk-Account:</label>
  <input name="OAuthLoginForm[wurzelwerk]" id="wurzelwerkAccount" type="text" class="form-control">

    <a href="https://www.netz.gruene.de/passwordForgotten.form" target="_blank"
      style="font-size: 0.8em; margin-top: -7px; display: inline-block; margin-bottom: 10px;">
        Wurzelwerk-Zugangsdaten vergessen?
    </a>
    </div>

    <button type="submit" class="btn btn-primary">
            <span class="glyphicon glyphicon-log-in"></span> Einloggen
    </button>
';
echo Html::endForm();
echo '<div id="loginWurzelwerkHint">
            <strong>Hinweis:</strong> Hier wirst du auf eine Seite unter "https://service.gruene.de/" umgeleitet,
            die vom Bundesverband betrieben wird.<br>Dort musst du dein Wurzelwerk-BenutzerInnenname/Passwort
            eingeben und bestätigen, dass deine E-Mail-Adresse an Antragsgrün übermittelt wird.
            Dein Wurzelwerk-Passwort bleibt geheim und wird <i>nicht</i> an Antragsgrün übermittelt.
        </div>
</div>';

if ($hide_ww_login) {
    echo '</div>';
}

if (!$controller->site || (!$controller->site->getSettings()->onlyNamespacedAccounts &&
        !$controller->site->getSettings()->onlyNamespacedAccounts)
) {
    echo '<h2>OpenID-Login</h2>
	<div class="content">';
    echo Html::beginForm('', 'post', ['class' => 'col-sm-5']);

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
    echo '</div>';
}
