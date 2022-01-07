<?php

use app\components\UrlHelper;
use app\models\db\ConsultationUserGroup;
use app\models\db\User;
use yii\helpers\Html;
use app\models\settings\Site as SiteSettings;

/**
 * @var yii\web\View $this
 * @var User[] $users
 * @var ConsultationUserGroup[] $groups
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('admin', 'users_head');
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'users_bc'));

$layout->loadVue();
$layout->addVueTemplate('@app/views/admin/index/users.vue.php');


echo '<h1>' . Yii::t('admin', 'siteacc_title') . '</h1>';

echo '<div class="content">';


$usersArr = array_map(function(User $user) {
    return ['username' => $user->getAuthUsername()];
}, $users);
$groupsArr = array_map(function(ConsultationUserGroup $group) {
    return ['title' => $group->title];
}, $groups);

?>

<div data-antragsgruen-widget="backend/UserAdmin"
     data-users="<?= Html::encode(json_encode($usersArr)) ?>"
     data-groups="<?= Html::encode(json_encode($groupsArr)) ?>"
>
    <div class="userAdmin"></div>
</div>

<?php
echo '</div>';
