<?php

use app\components\UrlHelper;
use app\models\db\User;
use yii\helpers\Html;
use app\models\settings\Site as SiteSettings;

/**
 * @var yii\web\View $this
 * @var \app\models\db\Site $site
 * @var bool $policyWarning
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('admin', 'siteacc_title');
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(\Yii::t('admin', 'siteacc_bread'));
$layout->loadFuelux();
$layout->setMainAMDModule('backend/SiteAccess');

$settings = $site->getSettings();

echo '<h1>' . \Yii::t('admin', 'siteacc_title') . '</h1>';

if ($policyWarning) {
    echo '<div class="accountEditExplanation alert alert-info alert-dismissible" role="alert">
<button type="button" class="close" data-dismiss="alert"
aria-label="Close"><span aria-hidden="true">&times;</span></button>' .
        Html::beginForm('', 'post', ['id' => 'policyRestrictForm']) . \Yii::t('admin', 'siteacc_policywarning') .
        '<div class="saveholder"><button type="submit" name="policyRestrictToUsers" class="btn btn-primary">' .
        \Yii::t('admin', 'siteacc_policy_login') . '</button></div>' .
        Html::endForm() . '</div>';
}


echo Html::beginForm('', 'post', ['id' => 'siteSettingsForm', 'class' => 'content adminForm form-horizontal']);

$success = \Yii::$app->session->getFlash('success_login', null, true);
if ($success) {
    echo '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">Success:</span>
                ' . Html::encode($success) . '
            </div>';
}

echo '<div class="checkbox forceLogin">
  <label>' . Html::checkbox('forceLogin', $settings->forceLogin) . \Yii::t('admin', 'siteacc_forcelogin') .
    '</label>
</div>';

echo '<div class="checkbox managedUserAccounts">
  <label>' . Html::checkbox('managedUserAccounts', $settings->managedUserAccounts) .
    \Yii::t('admin', 'siteacc_managedusers') . '</label>
</div>';


echo '<fieldset class="loginMethods"><legend>' . \Yii::t('admin', 'siteacc_logins') . ':</legend>';

$method = SiteSettings::LOGIN_STD;
echo '<div class="checkbox std"><label>';
if (User::getCurrentUser()->getAuthType() == SiteSettings::LOGIN_STD) {
    echo Html::checkbox('login[]', true, ['value' => $method, 'disabled' => 'disabled']);
} else {
    echo Html::checkbox('login[]', in_array($method, $settings->loginMethods), ['value' => $method]);
}
echo ' ' . \Yii::t('admin', 'siteacc_useraccounts') . '</label>
</div>';

if ($controller->getParams()->hasWurzelwerk || $controller->getParams()->hasSaml) {
    $method = SiteSettings::LOGIN_WURZELWERK;
    echo '<div class="checkbox wurzelwerk">
  <label>' . Html::checkbox('login[]', in_array($method, $settings->loginMethods), ['value' => $method]) .
        \Yii::t('admin', 'siteacc_ww') . '</label>
</div>';
}

$method = SiteSettings::LOGIN_EXTERNAL;
echo '<div class="checkbox external">
  <label>';
if (User::getCurrentUser()->getAuthType() == SiteSettings::LOGIN_EXTERNAL) {
    echo Html::checkbox('login[]', true, ['value' => $method, 'disabled']);
} else {
    echo Html::checkbox('login[]', in_array($method, $settings->loginMethods), ['value' => $method]);
}
echo ' ' . \Yii::t('admin', 'siteacc_otherlogins') . '</label>
</div>';

echo '</fieldset>';

echo '<div class="saveholder">
<button type="submit" name="saveLogin" class="btn btn-primary">' . \Yii::t('base', 'save') . '</button>
</div>';

echo Html::endForm();

if ($controller->consultation) {
    $consultation = $controller->consultation;
    include('site_access_accounts.php');
}


echo Html::beginForm('', 'post', ['id' => 'adminForm', 'class' => 'adminForm form-horizontal']);
echo '<h2 class="green">' . \Yii::t('admin', 'siteacc_admins_title') . '</h2>
    <section class="content">
    <ul style="margin-top: 10px;">';

$myself = User::getCurrentUser();
foreach ($site->admins as $admin) {
    echo '<li class="admin' . $admin->id . '">';
    echo Html::encode($admin->getAuthName());
    if ($admin->name != '') {
        echo ' (' . Html::encode($admin->name) . ')';
    }
    if ($admin->id != $myself->id) {
        echo '<button class="link removeAdmin" type="button" data-id="' . $admin->id . '">';
        echo '<span class="glyphicon glyphicon-trash"></span>';
        echo '</button>';
    }
    echo "</li>";
}
echo '</ul>

<br>

<h4>' . \Yii::t('admin', 'siteacc_admins_add') . '</h4>
<div class="row">
    <div class="col-md-3">';

$options = [
    'wurzelwerk' => \Yii::t('admin', 'siteacc_add_ww') . ':',
    'email'      => \Yii::t('admin', 'siteacc_add_email') . ':',
];
echo \app\components\HTMLTools::fueluxSelectbox('addType', $options);
echo '</div>
<div class="col-md-4">
    <input type="text" name="addUsername" value="" id="addUsername" class="form-control"
    title="' . Html::encode(\Yii::t('admin', 'siteacc_add_name_title')) . '"
    placeholder="' . Html::encode(\Yii::t('admin', 'siteacc_add_name_place')) . '" required>
</div>
<div class="col-md-3">
    <button type="submit" name="addAdmin" class="btn btn-primary">' . \Yii::t('admin', 'siteacc_add_btn') . '</button>
</div>
</div>
<br><br>
</section>';
echo Html::endForm();
