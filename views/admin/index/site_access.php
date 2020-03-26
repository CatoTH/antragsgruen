<?php

use app\components\UrlHelper;
use app\models\db\User;
use yii\helpers\Html;
use app\models\settings\Site as SiteSettings;

/**
 * @var yii\web\View $this
 * @var \app\models\db\Site $site
 * @var \app\models\db\Consultation $consultation
 * @var array $admins
 * @var bool $policyWarning
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('admin', 'siteacc_title');
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'siteacc_bread'));
$layout->loadFuelux();
$layout->addAMDModule('backend/SiteAccess');

$settings    = $site->getSettings();
$conSettings = $consultation->getSettings();

echo '<h1>' . Yii::t('admin', 'siteacc_title') . '</h1>';

if ($policyWarning) {
    echo '<div class="accountEditExplanation alert alert-info">' .
        Html::beginForm('', 'post', ['id' => 'policyRestrictForm']) . Yii::t('admin', 'siteacc_policywarning') .
        '<div class="saveholder"><button type="submit" name="policyRestrictToUsers" class="btn btn-primary">' .
        Yii::t('admin', 'siteacc_policy_login') . '</button></div>' .
        Html::endForm() . '</div>';
}


echo Html::beginForm('', 'post', ['id' => 'siteSettingsForm', 'class' => 'content adminForm form-horizontal']);

$success = Yii::$app->session->getFlash('success_login', null, true);
if ($success) {
    echo '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">' . Yii::t('base', 'aria_success') . ':</span>
                ' . Html::encode($success) . '
            </div>';
}

?>
    <div class="checkbox forceLogin">
        <label>
            <?= Html::checkbox('forceLogin', $conSettings->forceLogin) ?>
            <?= Yii::t('admin', 'siteacc_forcelogin') ?>
        </label>
    </div>

    <div class="checkbox managedUserAccounts">
        <label>
            <?= Html::checkbox('managedUserAccounts', $conSettings->managedUserAccounts) ?>
            <?= Yii::t('admin', 'siteacc_managedusers') ?>
        </label>
    </div>

<?php
$conPwd = new \app\components\ConsultationAccessPassword($consultation);
?>
    <div class="checkbox conpw <?= ($conPwd->isPasswordSet() ? 'hasPassword' : 'noPassword') ?>">
        <label class="setter">
            <?= Html::checkbox('pwdProtected', $conPwd->isPasswordSet()) ?>
            <?= Yii::t('admin', 'siteacc_con_pw') ?>
            <button class="btn btn-xs btn-default setNewPassword" type="button">
                <?= Yii::t('admin', 'siteacc_con_pw_set') ?>
            </button>
        </label>
        <div class="setPasswordHolder">
            <input type="password" name="consultationPassword" class="form-control"
                   placeholder="<?= Yii::t('admin', 'siteacc_con_pw_place') ?>"
                   title="<?= Yii::t('admin', 'siteacc_con_pw_set') ?>">
            <label class="otherConsultations">
                <input type="radio" name="otherConsultations" value="0"
                    <?= ($conPwd->allHaveSamePwd() ? '' : 'checked') ?>>
                <?= Yii::t('admin', 'siteacc_con_pw_set_this') ?>
            </label>
            <label class="otherConsultations">
                <input type="radio" name="otherConsultations" value="1"
                    <?= ($conPwd->allHaveSamePwd() ? 'checked' : '') ?>>
                <?= Yii::t('admin', 'siteacc_con_pw_set_all') ?>
            </label>
        </div>
    </div>


    <fieldset class="loginMethods">
        <legend><?= Yii::t('admin', 'siteacc_logins') ?>:</legend>

        <div class="checkbox std">
            <label>
                <?php
                $method = SiteSettings::LOGIN_STD;
                if (User::getCurrentUser()->getAuthType() == SiteSettings::LOGIN_STD) {
                    echo Html::checkbox('login[]', true, ['value' => $method, 'disabled' => 'disabled']);
                } else {
                    echo Html::checkbox('login[]', in_array($method, $settings->loginMethods), ['value' => $method]);
                }
                echo ' ' . Yii::t('admin', 'siteacc_useraccounts');
                ?>
            </label>
        </div>
        <?php
        if ($controller->getParams()->isSamlActive()) {
            $method = SiteSettings::LOGIN_WURZELWERK;
            echo '<div class="checkbox wurzelwerk"><label>' .
                Html::checkbox('login[]', in_array($method, $settings->loginMethods), ['value' => $method]) .
                ' ' . Yii::t('admin', 'siteacc_ww') .
                '</label></div>';
        }
        ?>
    </fieldset>

    <div class="saveholder">
        <button type="submit" name="saveLogin" class="btn btn-primary"><?= Yii::t('base', 'save') ?></button>
    </div>
<?php

echo Html::endForm();

if ($controller->consultation) {
    $consultation = $controller->consultation;
    include('_site_access_accounts.php');

    include('_site_access_admins.php');
}
