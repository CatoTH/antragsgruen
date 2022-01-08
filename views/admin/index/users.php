<?php

use app\components\UrlHelper;
use app\models\db\{ConsultationUserGroup, User};
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
