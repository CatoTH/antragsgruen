<?php

use app\components\UrlHelper;
use app\models\forms\LoginUsernamePasswordForm;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var LoginUsernamePasswordForm $usernamePasswordForm
 * @var string $msg_err
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Login';
$layout->addBreadcrumb('Login');


echo '<h1>Login</h1>';


if (!$controller->site || !$controller->site->getSettings()->onlyWurzelwerk) {
    $pwMinLen = \app\models\forms\LoginUsernamePasswordForm::PASSWORD_MIN_LEN;

    echo '<h2>Login per BenutzerInnenname / Passwort</h2>
    <div class="content">';

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

    if ($controller->site && $controller->site->getSettings()->onlyNamespacedAccounts) {
        echo '<div class="alert alert-info">!';
        // @TODO
        //echo veranstaltungsspezifisch_hinweis_namespaced_accounts($this->veranstaltung,
        //'<strong>Hinweis:</strong> wenn du berechtigt bist, (Änderungs-)Anträge einzustellen,
        //solltest du die Zugangsdaten per E-Mail erhalten haben.<br>
        //Falls du keine bekommen haben solltest, melde dich bitte bei den
        //Organisatoren dieses Parteitags / dieser Programmdiskussion.');
        echo "</div>";
    } else {
        $pre_checked = (isset($_REQUEST["createAccount"]) ? 'checked' : '');
        echo '<div class="checkbox"><label>
            <input type="checkbox" name="createAccount" id="createAccount" ' . $pre_checked . '>
            Neuen Zugang anlegen
            </label></div>';
    }

    echo '<div class="form-group">
        <label for="username">E-Mail-Adresse / BenutzerInnenname:</label>
            <input class="form-control" name="username" id="username" type="text" autofocus required
            placeholder="E-Mail-Adresse" value="' . Html::encode($preUsername) . '">
        </div>

        <div class="form-group">
            <label for="password_input">Passwort:</label>
            <input type="password" name="password" id="password_input" required class="form-control">
        </div>

        <div class="form-group"  id="pwd_confirm" style="display: none;">
            <label for="passwordConfirm">Passwort (Bestätigung):</label>
            <input type="password" name="passwordConfirm" id="passwordConfirm" class="form-control">
        </div>

        <div class="form-group" id="reg_name" style="display: none;">
            <label for="name">Dein Name:</label>
            <input type="text" value="' . Html::encode($preName) . '" name="name" id="name" class="form-control">
        </div>

        <script>
            $(function () {
                var $form = $("#usernamePasswordForm");
                $form.find("input[name=createAccount]").change(function () {
                    if ($(this).prop("checked")) {
                        $("#pwd_confirm").show();
                        $("#reg_name").show().find("input").attr("required", "required");
                        $("#password_input").attr("placeholder", "Min. ' . $pwMinLen . ' Zeichen");
                        $("#create_str").show();
                        $("#login_str").hide();
                    } else {
                        $("#pwd_confirm").hide();
                        $("#reg_name").hide().find("input").removeAttr("required");
                        $("#password_input").attr("placeholder", "");
                        $("#create_str").hide();
                        $("#login_str").show();
                    }
                }).trigger("change");
                $form.submit(function(ev) {
                    var pwd = $("#password_input").val();
                    if (pwd.length < 4) {
                        ev.preventDefault();
                        alert("Das Passwort muss mindestens 4 Buchstaben haben.");
                    }
                    if ($form.find("input[name=createAccount]").prop("checked")) {
                        if (pwd != $("#passwordConfirm").val()) {
                            ev.preventDefault();
                            alert("Die beiden Passwörter stimmen nicht überein.");
                        }
                    }
                });
            })
        </script>
        <button type="submit" class="btn btn-primary" name="loginusernamepassword">

            <span id="login_str"><span class="glyphicon glyphicon-log-in"></span> Einloggen</span>
            <span id="create_str"><span class="glyphicon glyphicon-plus-sign"></span> Anlegen</span>
        </button>
        ';
    echo Html::endForm();

    echo '</div>';
}

/** @var \app\models\settings\AntragsgruenApp $params */
$params = \Yii::$app->params;
if ($params->hasWurzelwerk) {
    $hide_ww_login = ($controller->site && $controller->site->getSettings()->onlyNamespacedAccounts);
    if ($hide_ww_login) {
        echo '<div class="content">
        <a href="#" onClick="$(\'#admin_login_www\').toggle(); return false;">Admin-Login</a>
    </div>
    <div id="admin_login_www" style="display: none;">';
    }

    echo '<h2>Wurzelwerk-Login</h2>
    <div class="content">';

    $backUrl = UrlHelper::createUrl('consultation/index');
    $action  = UrlHelper::createUrl(['user/loginwurzelwerk', 'backUrl' => $backUrl]);
    echo Html::beginForm($action, 'post', ['class' => 'col-sm-4']);

    echo '<div class="form-group">
    <label for="wurzelwerkAccount">WurzelWerk-Account:</label>
  <input name="username" id="wurzelwerkAccount" type="text" class="form-control">
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
    <br><br>
    <a href="https://netz.gruene.de/passwordForgotten.form" class="loginWurzelwerkForgot" target="_blank">
        Wurzelwerk-Zugangsdaten vergessen?
    </a>
        </div>
</div>';

    if ($hide_ww_login) {
        echo '</div>';
    }
}


if (!$controller->site || (!$controller->site->getSettings()->onlyNamespacedAccounts &&
        !$controller->site->getSettings()->onlyNamespacedAccounts)
) {
    echo '<h2>OpenID-Login</h2>
	<div class="content">';
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
    echo '</div>';
}
