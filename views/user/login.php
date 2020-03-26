<?php

use app\components\UrlHelper;
use app\models\forms\LoginUsernamePasswordForm;
use yii\helpers\Html;
use app\models\settings\Site as SiteSettings;

/**
 * @var yii\web\View $this
 * @var LoginUsernamePasswordForm $usernamePasswordForm
 * @var string $backUrl
 * @var string|null $conPwdErr
 * @var \app\models\db\Consultation $conPwdConsultation
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('user', 'login_title');
$layout->addBreadcrumb(Yii::t('user', 'login_title'));
$layout->robotsNoindex = true;
$layout->addAMDModule('frontend/LoginForm');

if ($controller->site) {
    $loginMethods = $controller->site->getSettings()->loginMethods;
} else {
    $loginMethods = SiteSettings::$SITE_MANAGER_LOGIN_METHODS;
}
/** @var \app\models\settings\AntragsgruenApp $params */
$params = Yii::$app->params;

echo '<h1>' . Yii::t('user', 'login_title') . '</h1>';

$loginText = \app\models\db\ConsultationText::getPageData($controller->site, $controller->consultation, 'login_pre');
if ($loginText && trim($loginText->text) !== '') {
    echo '<div class="content contentPage">';
    echo $loginText->text;
    echo '</div>';
}

$shownAccessPwdForm = false;
if ($controller->consultation && $controller->consultation->getSettings()->accessPwd) {
    $conPwd = new \app\components\ConsultationAccessPassword($controller->consultation);
    if (!$conPwd->isCookieLoggedIn()) {
        $shownAccessPwdForm = true;
        ?>
        <section class="loginConPwd">
            <h2 class="green"><?= Yii::t('user', 'login_con_pwd_title') ?></h2>
            <?= Html::beginForm('', 'post', ['class' => 'content row', 'id' => 'conPwdForm']) ?>
            <div class="content">
                <?php
                if ($conPwdErr) {
                    echo '<div class="alert alert-danger" role="alert">
                    <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                    <span class="sr-only">' . Yii::t('base', 'aria_error') . ':</span>';
                    echo Html::encode($conPwdErr);
                    echo '</div>';
                }
                ?>
                <div class="row content">
                    <label><?= Yii::t('motion', 'consultation') ?>:</label>
                    <div>
                        <?= Html::encode($conPwdConsultation->title) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="conpwd"><?= Yii::t('user', 'login_con_pwd') ?>:</label>
                        <input type="password" value="" name="password" id="conpwd" class="form-control"
                               autocomplete="current-password">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary" name="loginconpwd">
                            <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
                            <?= Yii::t('user', 'login_btn_login') ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php
            if (in_array(SiteSettings::LOGIN_STD, $loginMethods)) {
                echo '<div class="usernameLoginOpener" style="padding-left: 20px;">
                    <a href="#" onClick="$(\'.loginUsername\').toggleClass(\'hidden\'); $(\'#username\').trigger(\'focus\').scrollintoview({top_offset: 100}); return false;">' .
                     Yii::t('user', 'login_username_title') .
                     '</a>
                </div>';
            }
            ?>
            <?= Html::endForm() ?>
        </section>
        <?php
    }
}

if (in_array(SiteSettings::LOGIN_STD, $loginMethods)) {
    $pwMinLen = LoginUsernamePasswordForm::PASSWORD_MIN_LEN;

    $classes = ['loginUsername'];
    if ($shownAccessPwdForm) {
        $classes[] = 'hidden';
    }

    echo '<section class="' . implode(' ', $classes) . '">
    <h2 class="green">' . Yii::t('user', 'login_username_title') . '</h2>
    <div class="content row">';

    if ($usernamePasswordForm->error) {
        echo '<div class="alert alert-danger" role="alert">
  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
  <span class="sr-only">Error:</span>';
        echo Html::encode($usernamePasswordForm->error);
        echo '</div>';
    }

    echo Html::beginForm('', 'post', ['class' => 'col-sm-6', 'id' => 'usernamePasswordForm']);

    $preUsername = $usernamePasswordForm->username;
    $preName     = $usernamePasswordForm->name;

    $preChecked = (isset($_REQUEST["createAccount"]) ? 'checked' : '');
    ?>
    <div class="checkbox">
        <label>
            <input type="checkbox" name="createAccount" id="createAccount" <?= $preChecked ?>>
            <?= Yii::t('user', 'login_create_account') ?>
        </label>
    </div>

    <?php
    if ($controller->consultation && $controller->consultation->getSettings()->managedUserAccounts) {
        echo '<div class="alert alert-info managedAccountHint hidden"><p>';
        echo Yii::t('user', 'login_managed_hint');
        echo '</p></div>';
    }
    ?>

    <div class="form-group">
        <label for="username"><?= Yii::t('user', 'login_username') ?>:</label>
        <input class="form-control" name="username" id="username" type="text" autofocus required
               placeholder="<?= Html::encode(Yii::t('user', 'login_email_placeholder')) ?>"
               autocomplete="username" value="<?= Html::encode($preUsername) ?>">
    </div>

    <div class="form-group">
        <label for="passwordInput"><?= Yii::t('user', 'login_password') ?>:</label>
        <input type="password" name="password" id="passwordInput" required class="form-control"
               autocomplete="current-password" data-min-len="<?= $pwMinLen ?>">
    </div>

    <div class="form-group hidden" id="pwdConfirm">
        <label for="passwordConfirm"><?= Yii::t('user', 'login_password_rep') ?>:</label>
        <input type="password" name="passwordConfirm" id="passwordConfirm" class="form-control">
    </div>

    <div class="form-group hidden" id="regName">
        <label for="name"><?= Yii::t('user', 'login_create_name') ?>:</label>
        <input type="text" value="<?= Html::encode($preName) ?>" name="name" id="name" class="form-control">
    </div>

    <?php
    if ($controller->getParams()->dataPrivacyCheckbox) {
        ?>
        <div class="form-group hidden checkbox" id="regConfirmation">
            <label>
                <input type="checkbox" name="confirmation" id="confirmation">
                <?= Yii::t('user', 'login_confirm_registration') ?>:
            </label>
        </div>
        <?php
    }
    ?>

    <div class="row">
        <div class="col-md-6">
            <button type="submit" class="btn btn-primary" name="loginusernamepassword">
                <span id="loginStr"><span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
                    <?= Yii::t('user', 'login_btn_login') ?></span>
                <span id="createStr"><span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                    <?= Yii::t('user', 'login_btn_create') ?></span>
            </button>
        </div>
        <div class="col-md-6 passwordRecovery">
            <?= Html::a(Yii::t('user', 'login_forgot_pw'), UrlHelper::createUrl('user/recovery')) ?>
        </div>
    </div>
    <?php
    echo Html::endForm();

    echo '</div>
    </section>';
}


if ($params->isSamlActive()) {
    $hide_ww_login = !in_array(SiteSettings::LOGIN_WURZELWERK, $loginMethods);
    echo '<section class="loginSimplesaml">';
    if ($hide_ww_login) {
        echo '<div class="content">
        <a href="#" onClick="$(\'#admin_login_saml\').toggleClass(\'hidden\'); return false;">Admin-Login</a>
    </div>
    <div id="admin_login_saml" class="hidden">';
    }

    echo '<h2 class="green">&quot;Grünes Netz&quot;-Login (Wurzelwerk)</h2>
    <div class="content row">';

    $action = $params->domainPlain . 'loginsaml';
    echo Html::beginForm($action, 'post', ['class' => 'col-sm-4', 'id' => 'samlLoginForm']);

    $absoluteBack = UrlHelper::absolutizeLink($backUrl);
    echo '
        <input type="hidden" name="backUrl" value="' . Html::encode($absoluteBack) . '">
        <button type="submit" class="btn btn-primary" name="samlLogin">
            <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span> Einloggen
    </button>';

    echo Html::endForm();
    echo '<div id="loginSamlHint">
    <strong>Hinweis:</strong> Hier wirst du auf eine Seite unter "https://saml.gruene.de/" umgeleitet,
    die vom Bundesverband betrieben wird.<br>Dort musst du dein Benutzer*innenname/Passwort des Grünen Netzes
    (Wurzelwerk) eingeben. Dein Passwort bleibt dabei geheim und wird <i>nicht</i> an Antragsgrün übermittelt.
    <br><br>
    <strong>Zugangsdaten vergessen?</strong> Klicke auf „Einloggen” und auf der folgenden Seite auf „Passwort vergessen?”.
        </div>
</div>';

    if ($hide_ww_login) {
        echo '</div>';
    }
    echo '</section>';
}

$loginText = \app\models\db\ConsultationText::getPageData($controller->site, $controller->consultation, 'login_post');
if ($loginText && trim($loginText->text) !== '') {
    echo '<div class="content contentPage">';
    echo $loginText->text;
    echo '</div>';
}

