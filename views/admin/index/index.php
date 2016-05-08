<?php

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


echo '<h1>' . \Yii::t('admin', 'index_settings') . '</h1>';


echo $controller->showErrors();

echo '
    <div class="content adminIndex">
    <ul>
    <li>';

$link = UrlHelper::createUrl('admin/index/consultation');
echo Html::a(\Yii::t('admin', 'index_consultation_settings'), $link, ['id' => 'consultationLink']);

echo '</li><li class="secondary">';
echo Html::a(
    Yii::t('admin', 'Translation / Wording'),
    UrlHelper::createUrl('admin/index/translation'),
    ['id' => 'translationLink']
);
if (!$consultation->hasHelpPage()) {
    echo '</li><li class="secondary">';
    echo Html::a(
        Yii::t('admin', 'help_page_create'),
        UrlHelper::createUrl('consultation/help'),
        ['id' => 'helpCreateLink']
    );
}
echo '</li>';


echo '<li>' . \Yii::t('admin', 'index_motion_types') . '<ul>';
foreach ($consultation->motionTypes as $motionType) {
    echo '<li>';
    $sectionsUrl = UrlHelper::createUrl(['admin/motion/type', 'motionTypeId' => $motionType->id]);
    echo Html::a($motionType->titlePlural, $sectionsUrl, ['class' => 'motionType' . $motionType->id]);
    echo '</li>';
}
echo '<li class="secondary motionTypeCreate">';
echo Html::a(\Yii::t('admin', 'motion_type_create_caller'), UrlHelper::createUrl(['admin/motion/typecreate']));
echo '</li>';
echo '</ul></li>';

echo '<li>';
echo Html::a(
    \Yii::t('admin', 'index_site_access'),
    UrlHelper::createUrl('admin/index/siteaccess'),
    ['class' => 'siteAccessLink']
);
echo '</li><li>';
echo Html::a(
    \Yii::t('admin', 'index_site_consultations'),
    UrlHelper::createUrl('admin/index/siteconsultations'),
    ['class' => 'siteConsultationsLink']
);
echo '</li>';

if (User::currentUserIsSuperuser()) {
    echo '<li>';
    echo Html::a(
        \Yii::t('admin', 'index_site_user_list'),
        UrlHelper::createUrl('manager/userlist'),
        ['class' => 'siteUserList']
    );
    echo '</li>';

    echo '<li>';
    echo Html::a(
        \Yii::t('admin', 'index_site_config'),
        UrlHelper::createUrl('manager/siteconfig'),
        ['class' => 'siteConfigLink']
    );
    echo '</li>';
}

echo '</ul>';


if (User::currentUserIsSuperuser()) {
    echo Html::beginForm('', 'post', ['class' => 'sysadminForm']);
    echo '<button type="submit" name="flushCaches" class="btn btn-small btn-default">' .
        \Yii::t('admin', 'index_flush_caches') . '</button>';
    echo Html::endForm();
}


echo '</div>';

