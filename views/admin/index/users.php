<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var array $widgetData
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('admin', 'users_head');
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'users_bc'));

$layout->loadVue();
$layout->loadVueSelect();
$layout->addVueTemplate('@app/views/admin/index/users.vue.php');

$userSaveUrl = UrlHelper::createUrl(['/admin/index/users-save']);
$userPollUrl = UrlHelper::createUrl(['/admin/index/users-poll']);

echo '<h1>' . Yii::t('admin', 'siteacc_title') . '</h1>';

echo '<div class="content">';

echo $controller->showErrors();

$success = Yii::$app->session->getFlash('success_login', null, true);
if ($success) {
    echo '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">' . Yii::t('base', 'aria_success') . ':</span>
                ' . Html::encode($success) . '
            </div>';
}


?>

<div data-antragsgruen-widget="backend/UserAdmin"
     data-url-user-save="<?= Html::encode($userSaveUrl) ?>"
     data-url-poll="<?= Html::encode($userPollUrl) ?>"
     data-users="<?= Html::encode(json_encode($widgetData['users'])) ?>"
     data-groups="<?= Html::encode(json_encode($widgetData['groups'])) ?>"
>
    <div class="userAdmin"></div>
</div>

<?php
echo '</div>';


echo $this->render('_users_add_accounts');
