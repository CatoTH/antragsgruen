<?php

use app\components\UrlHelper;
use app\models\db\UserConsultationScreening;
use app\models\layoutHooks\Layout;
use app\models\settings\Privilege;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var array $widgetData
 * @var UserConsultationScreening[] $screening
 * @var bool $globalUserAdmin
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$layout = $controller->layoutParams;

$this->title = Yii::t('admin', 'users_head');
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'users_bc'));

$layout->loadVue();
$layout->loadSelectize();
$layout->addVueTemplate('@app/views/shared/selectize.vue.php');
$layout->addVueTemplate('@app/views/admin/users/users.vue.php');
$layout->addVueTemplate('@app/views/admin/users/_user_edit.vue.php');
$layout->addVueTemplate('@app/views/admin/users/_group_edit.vue.php');
$layout->addVueTemplate('@app/views/admin/users/_organisation_edit.vue.php');
Layout::registerAdditionalVueUserAdministrationTemplates($consultation, $layout);

$userSaveUrl = UrlHelper::createUrl(['/admin/users/save']);
$userPollUrl = UrlHelper::createUrl(['/admin/users/poll']);
$userLogUrl = UrlHelper::createUrl(['/consultation/activitylog', 'userId' => '###USER###']);
$userGroupLogUrl = UrlHelper::createUrl(['/consultation/activitylog', 'userGroupId' => '###GROUP###']);

echo '<h1>' . Yii::t('admin', 'siteacc_accounts_title') . '</h1>';

echo $controller->showErrors();

$success = \app\components\RequestContext::getSession()->getFlash('success_login', null, true);
if ($success) {
    echo '<div class="content">';
    echo '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">' . Yii::t('base', 'aria_success') . ':</span>
                ' . Html::encode($success) . '
            </div>';
    echo '</div>';
}


$privileges = \app\models\settings\Privileges::getPrivileges($consultation);
$nonMotionPrivileges = array_values(array_map(fn (Privilege $priv): array => [
    'id' => $priv->id,
    'title' => $priv->name,
], $privileges->getNonMotionPrivileges()));
$motionPrivileges = array_values(array_map(fn (Privilege $priv): array => [
    'id' => $priv->id,
    'title' => $priv->name,
] , $privileges->getMotionPrivileges()));
$agendaItems = array_map(fn (\app\models\db\ConsultationAgendaItem $item): array => [
    'id' => $item->id,
    'title' => $item->title,
], $consultation->agendaItems);
$tags = array_map(fn (\app\models\db\ConsultationSettingsTag $tag): array => [
    'id' => $tag->id,
    'title' => $tag->title,
], $consultation->tags);
$motionTypes = array_map(fn(\app\models\db\ConsultationMotionType $type): array => [
    'id' => $type->id,
    'title' => $type->titlePlural,
], $consultation->motionTypes);
$privilegeDependencies = $privileges->getPrivilegeDependencies();
?>

<div data-antragsgruen-widget="backend/UserAdmin"
     data-url-user-save="<?= Html::encode($userSaveUrl) ?>"
     data-url-poll="<?= Html::encode($userPollUrl) ?>"
     data-url-user-log="<?= Html::encode($userLogUrl) ?>"
     data-url-user-group-log="<?= Html::encode($userGroupLogUrl) ?>"
     data-users="<?= Html::encode(json_encode($widgetData['users'])) ?>"
     data-groups="<?= Html::encode(json_encode($widgetData['groups'])) ?>"
     data-organisations="<?= Html::encode(json_encode($consultation->getSettings()->organisations ?? [])) ?>"
     data-permission-global-edit="<?= $globalUserAdmin ? '1' : '0' ?>"
     data-non-motion-privileges="<?= Html::encode(json_encode($nonMotionPrivileges)) ?>"
     data-motion-privileges="<?= Html::encode(json_encode($motionPrivileges)) ?>"
     data-agenda-items="<?= Html::encode(json_encode($agendaItems)) ?>"
     data-tags="<?= Html::encode(json_encode($tags)) ?>"
     data-motion-types="<?= Html::encode(json_encode($motionTypes)) ?>"
     data-privilege-dependencies="<?= Html::encode(json_encode($privilegeDependencies)) ?>"
>
    <div class="userAdmin"></div>
</div>

<?php


echo $this->render('_users_add_accounts', ['consultation' => $consultation]);
if (count($screening) > 0) {
    echo $this->render('_users_screening', ['screening' => $screening]);
}
