<?php

use app\components\UrlHelper;
use app\models\db\UserConsultationScreening;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var array $widgetData
 * @var UserConsultationScreening $screening
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

echo '<h1>' . Yii::t('admin', 'siteacc_accounts_title') . '</h1>';

echo $controller->showErrors();

$success = Yii::$app->session->getFlash('success_login', null, true);
if ($success) {
    echo '<div class="content">';
    echo '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">' . Yii::t('base', 'aria_success') . ':</span>
                ' . Html::encode($success) . '
            </div>';
    echo '</div>';
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


echo $this->render('_users_add_accounts');
if (count($screening) > 0) {
    echo $this->render('_users_screening', ['screening' => $screening]);
}
