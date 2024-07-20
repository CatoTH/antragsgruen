<?php

use app\models\db\ConsultationText;
use app\models\settings\AntragsgruenApp;
use app\components\{Captcha, UrlHelper};
use app\models\db\User;
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
    $loginMethods = SiteSettings::SITE_MANAGER_LOGIN_METHODS;
}
$params = AntragsgruenApp::getInstance();

$externalAuthenticator = User::getExternalAuthenticator();
$hasNonUsernamePwdLogin = false;

echo '<h1>' . Yii::t('user', 'login_title') . '</h1>';

$loginText = ConsultationText::getPageData($controller->site, $controller->consultation, ConsultationText::DEFAULT_PAGE_LOGIN_PRE);
if (trim($loginText->text) !== '') {
    echo '<div class="content contentPage">';
    echo $loginText->text;
    echo '</div>';
}

$shownAccessPwdForm = false;
if ($controller->consultation && $controller->consultation->getSettings()->accessPwd) {
    $hasNonUsernamePwdLogin = true;
    $conPwd = new \app\components\ConsultationAccessPassword($controller->consultation);
    if (!$conPwd->isCookieLoggedIn()) {
        $shownAccessPwdForm = true;
        ?>
        <section class="loginConPwd">
            <h2 class="green"><?= Yii::t('user', 'login_con_pwd_title') ?></h2>
            <div class="content">
                <?= Html::beginForm('', 'post', ['id' => 'conPwdForm']) ?>
                <?php
                if ($conPwdErr) {
                    echo '<div class="alert alert-danger" role="alert">
                    <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                    <span class="sr-only">' . Yii::t('base', 'aria_error') . ':</span>';
                    echo Html::encode($conPwdErr);
                    echo '</div>';
                }
                ?>
                <div class="consultationName">
                    <label><?= Yii::t('motion', 'consultation') ?>:</label>
                    <div>
                        <?= Html::encode($conPwdConsultation->title) ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="conpwd"><?= Yii::t('user', 'login_con_pwd') ?>:</label>
                    <input type="password" value="" name="password" id="conpwd" class="form-control"
                           autocomplete="current-password">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" name="loginconpwd">
                        <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
                        <?= Yii::t('user', 'login_btn_login') ?>
                    </button>
                </div>
                <?php
                if (in_array(SiteSettings::LOGIN_STD, $loginMethods)) {
                    echo '<div class="usernameLoginOpener">
                        <button type="button" class="btn btn-link" onClick="$(\'.loginUsername\').toggleClass(\'hidden\'); $(\'#username\').trigger(\'focus\').scrollintoview({top_offset: 100}); return false;">' .
                         Yii::t('user', 'login_username_title') .
                         '</a>
                    </div>';
                }
                ?>
                <?= Html::endForm() ?>
            </div>
        </section>
        <?php
    }
}

foreach (AntragsgruenApp::getActivePlugins() as $plugins) {
    if ($login = $plugins::getDedicatedLoginProvider()) {
        $hasNonUsernamePwdLogin = true;
        echo $login->renderLoginForm($backUrl, in_array($login->getId(), $loginMethods));
    }
}

if (in_array(SiteSettings::LOGIN_STD, $loginMethods)) {
    $pwMinLen         = LoginUsernamePasswordForm::PASSWORD_MIN_LEN;
    $supportsCreating = $usernamePasswordForm->supportsCreatingAccounts();

    $classes = ['loginUsername'];
    if ($shownAccessPwdForm) {
        $classes[] = 'hidden';
    }

    echo '<section class="' . implode(' ', $classes) . '">
    <h2 class="green' . ($hasNonUsernamePwdLogin ? '' : ' hidden') . '">' . Yii::t('user', 'login_username_title') . '</h2>
    <div class="content">';

    if ($usernamePasswordForm->error) {
        echo '<div class="alert alert-danger passwordError" role="alert">
  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
  <span class="sr-only">Error:</span>';
        echo Html::encode($usernamePasswordForm->error);
        echo '</div>';
    }

    echo Html::beginForm('', 'post', ['id' => 'usernamePasswordForm']);

    $preUsername = $usernamePasswordForm->username;
    $preName     = $usernamePasswordForm->name;

    if ($supportsCreating) {
        $preChecked = (isset($_REQUEST["createAccount"]) ? 'checked' : '');
        ?>
        <div class="checkbox">
            <label>
                <input type="checkbox" name="createAccount" id="createAccount" <?= $preChecked ?>>
                <?= Yii::t('user', 'login_create_account') ?>
            </label>
        </div>
        <?php
    }

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
               autocomplete="username" value="<?= Html::encode($preUsername ?: '') ?>">
    </div>

    <div class="form-group">
        <label for="passwordInput"><?= Yii::t('user', 'login_password') ?>:</label>
        <input type="password" name="password" id="passwordInput" required class="form-control"
               autocomplete="current-password" data-min-len="<?= $pwMinLen ?>">
    </div>
    <?php
    if ($supportsCreating) {
        ?>
        <div class="form-group hidden" id="pwdConfirm">
            <label for="passwordConfirm"><?= Yii::t('user', 'login_password_rep') ?>:</label>
            <input type="password" name="passwordConfirm" id="passwordConfirm" class="form-control">
        </div>

        <div class="form-group hidden" id="regName">
            <label for="name"><?= Yii::t('user', 'login_create_name') ?>:</label>
            <input type="text" value="<?= Html::encode($preName ?: '') ?>" name="name" id="name" class="form-control">
        </div>
        <?php
        if ($controller->getParams()->dataPrivacyCheckbox) {
            ?>
            <div class="form-group hidden checkbox" id="regConfirmation">
                <label>
                    <input type="checkbox" name="confirmation" id="confirmation">
                    <?= Yii::t('user', 'login_confirm_registration') ?>
                </label>
            </div>
            <?php
        }
    }

    if (Captcha::needsCaptcha(null)) {
        $image = Captcha::createInlineCaptcha();
        ?>
        <label for="captchaInput"><?= Yii::t('user', 'login_captcha') ?>:</label><br>
        <div class="captchaHolder">
            <img src="<?= $image ?>" alt="" width="150">
            <input type="text" value="" autocomplete="off" name="captcha" id="captchaInput" class="form-control" required>
        </div>
        <br><br>
        <?php
    }
    ?>

        <button type="submit" class="btn btn-primary" name="loginusernamepassword">
            <span id="loginStr"><span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
                <?= Yii::t('user', 'login_btn_login') ?></span>
            <?php
            if ($supportsCreating) {
                ?>
                <span id="createStr"><span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                <?= Yii::t('user', 'login_btn_create') ?></span>
                <?php
            }
            ?>
        </button>

        <?php
        if ($externalAuthenticator === null || $externalAuthenticator->supportsResetPassword()) {
            ?>
            <div class="passwordRecovery">
                <?= Html::a(Yii::t('user', 'login_forgot_pw'), UrlHelper::createUrl('user/recovery')) ?>
            </div>
            <?php
        } elseif ($externalAuthenticator->resetPasswordAlternativeLink()) {
            ?>
            <div class="passwordRecovery">
                <?= Html::a(Yii::t('user', 'login_forgot_pw'), $externalAuthenticator->resetPasswordAlternativeLink()) ?>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
    echo Html::endForm();

    echo '</div>
    </section>';
}

// Special handling for the main installation of Antragsgrün
$hide_ww_login = ($params->isSamlActive() && !in_array(SiteSettings::LOGIN_GRUENES_NETZ, $loginMethods));
if ($hide_ww_login) {
    echo '<div class="content">
        <a href="#" onClick="$(\'#admin_login_saml\').toggleClass(\'hidden\'); return false;">Admin-Login</a>
    </div>';
}

$loginText = ConsultationText::getPageData($controller->site, $controller->consultation, ConsultationText::DEFAULT_PAGE_LOGIN_POST);
if (trim($loginText->text) !== '') {
    echo '<div class="content contentPage">';
    echo $loginText->text;
    echo '</div>';
}

