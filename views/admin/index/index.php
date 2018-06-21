<?php

use app\components\updater\UpdateChecker;
use app\components\UrlHelper;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\AdminTodoItem[] $todo
 * @var \app\models\db\Site $site
 * @var \app\models\db\Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('admin', 'index_title');
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_settings'));
$layout->addAMDModule('backend/AdminIndex');

echo '<h1>' . \Yii::t('admin', 'index_settings') . '</h1>';


echo $controller->showErrors();

echo '<div class="content adminIndex"><div class="adminIndexHolder"><div>';

echo '<ul class="adminMenuList"><li>';

echo Html::a(
    \Yii::t('admin', 'index_consultation_settings'),
    UrlHelper::createUrl('admin/index/consultation'),
    ['id' => 'consultationLink']
);

echo '</li><li class="secondary">';
echo Html::a(
    Yii::t('admin', 'Translation / Wording'),
    UrlHelper::createUrl('admin/index/translation'),
    ['id' => 'translationLink']
);
echo '</li>';

echo '<li class="secondary">';
echo Html::a(
    Yii::t('admin', 'index_pages'),
    UrlHelper::createUrl('pages/list-pages'),
    ['id' => 'contentPages']
);
echo '</li>';

echo '<li>' . \Yii::t('admin', 'index_motion_types') . '<ul>';
foreach ($consultation->motionTypes as $motionType) {
    echo '<li>';
    $sectionsUrl = UrlHelper::createUrl(['admin/motion/type', 'motionTypeId' => $motionType->id]);
    echo Html::a(Html::encode($motionType->titlePlural), $sectionsUrl, ['class' => 'motionType' . $motionType->id]);
    echo '</li>';
}
echo '<li class="secondary motionTypeCreate">';
echo Html::a(\Yii::t('admin', 'motion_type_create_caller'), UrlHelper::createUrl(['admin/motion/typecreate']));
echo '</li>';
echo '</ul></li>';

if (User::havePrivilege($consultation, User::PRIVILEGE_SITE_ADMIN)) {
    echo '<li>';
    echo Html::a(
        \Yii::t('admin', 'index_site_access'),
        UrlHelper::createUrl('admin/index/siteaccess'),
        ['class' => 'siteAccessLink']
    );
    echo '</li>';

    echo '<li>';
    echo Html::a(
        \Yii::t('admin', 'index_site_consultations'),
        UrlHelper::createUrl('admin/index/siteconsultations'),
        ['class' => 'siteConsultationsLink']
    );
    echo '</li>';
}

if (User::currentUserIsSuperuser()) {
    echo '<li>';
    echo Html::a(
        \Yii::t('admin', 'index_site_user_list'),
        UrlHelper::createUrl('admin/userlist'),
        ['class' => 'siteUserList']
    );
    echo '</li>';

    echo '<li>';
    echo Html::a(
        \Yii::t('admin', 'index_site_config'),
        UrlHelper::createUrl('admin/siteconfig'),
        ['class' => 'siteConfigLink']
    );
    echo '</li>';
}

echo '</ul>';
echo '</div><aside class="adminIndexSecondary">';

echo $controller->getParams()->getBehaviorClass()->getAdminIndexHint($consultation);

if (User::currentUserIsSuperuser() && UpdateChecker::isUpdaterAvailable()) {
    $url = UrlHelper::createUrl('admin/index/check-updates');
    echo '<article class="adminCard adminCardUpdates">';
    echo '<header><h2>' . \Yii::t('admin', 'updates_title') . '</h2></header>';
    echo '<main data-src="' . Html::encode($url) . '">';
    echo \Yii::t('admin', 'updates_loading');
    echo '</main></article>';
}

if (User::currentUserIsSuperuser()) {
    echo Html::beginForm('', 'post', ['class' => 'sysadminForm']);
    echo '<button type="submit" name="flushCaches" class="btn btn-small btn-default">' .
        \Yii::t('admin', 'index_flush_caches') . '</button>';
    echo Html::endForm();
}


echo '</aside></div>';

if (User::havePrivilege($consultation, User::PRIVILEGE_CONSULTATION_SETTINGS)) {
    if (count($site->consultations) === 1) {
        echo Html::beginForm('', 'post', ['class' => 'delSiteCaller']);
        echo '<button class="btn-link" type="submit" name="delSite">' .
            '<span class="glyphicon glyphicon-trash"></span> ' . \Yii::t('admin', 'index_site_del') .
            '</button>';
        echo Html::endForm();
    }
}

echo '</div>';
