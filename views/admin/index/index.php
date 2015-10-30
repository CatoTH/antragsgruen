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
$layout->addBreadcrumb(\Yii::t('admin', 'bread_admin'));


echo '<h1>' . \Yii::t('admin', 'index_title') . '</h1>';


echo $controller->showErrors();

if (count($todo) > 0) {
    echo '<div class="row" style="margin-top: 20px;">';
    echo '<div class="col-md-7">';
} else {
    echo '<div style="margin-top: 20px;">';
}


echo '<h2 class="green">' . \Yii::t('admin', 'index_motions') . '</h2>
    <div class="content adminIndex">';

echo '<h3>' . Html::a(
        \Yii::t('admin', 'index_all_motions'),
        UrlHelper::createUrl('admin/motion/listall'),
        ['class' => 'motionListAll']
    ) . '</h3>';
echo '<ul>';
$odsUrl = UrlHelper::createUrl('admin/motion/odslistall');
echo '<li>' . Html::a(\Yii::t('admin', 'index_export_ods'), $odsUrl) . '</li>';
$htmlUrl = UrlHelper::createUrl('admin/motion/htmllistall');
echo '<li>' . Html::a(\Yii::t('admin', 'index_export_html'), $htmlUrl) . '</li>';
echo '</ul>';

foreach ($consultation->motionTypes as $motionType) {
    echo '<h3>' . Html::encode($motionType->titlePlural) . '</h3>
    <ul class="motionTypeSection' . $motionType->id . '">';

    $motionp = $motionType->getMotionPolicy();
    if ($motionp->checkCurrUserMotion()) {
        $createUrl = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionType->id]);
        echo '<li>' . Html::a(\Yii::t('admin', 'index_motion_create'), $createUrl, ['class' => 'createLink']) . '</li>';
    } else {
        echo '<li>' . \Yii::t('admin', 'index_motion_create') .
            ': <em>' . $motionp->getPermissionDeniedMotionMsg() . '</em></li>';
    }

    $odsUrl = UrlHelper::createUrl(['admin/motion/odslist', 'motionTypeId' => $motionType->id]);
    echo '<li class="secondary">';
    echo Html::a(\Yii::t('admin', 'index_export_ods'), $odsUrl, ['class' => 'motionODS' . $motionType->id]) . '</li>';

    $excelUrl = UrlHelper::createUrl(['admin/motion/excellist', 'motionTypeId' => $motionType->id]);
    echo '<li class="secondary">';
    echo Html::a(\Yii::t('admin', 'index_export_excel'), $excelUrl, ['class' => 'motionExcel' . $motionType->id]) .
        ' (' . \Yii::t('admin', 'index_error_prone') . ')</li>';

    echo '</ul>';
}

$amendmentOdsLink = UrlHelper::createUrl('admin/amendment/odslist');
$amendmentPDFLink = UrlHelper::createUrl('admin/amendment/pdflist');
echo '<h3>' . \Yii::t('admin', 'index_amendments') . '</h3>
<ul>
    <li>' .
    Html::a(\Yii::t('admin', 'index_pdf_collection'), UrlHelper::createUrl('amendment/pdfcollection'), ['class' => 'amendmentsPdf']) .
    '</li>
    <li class="secondary">' . Html::a(\Yii::t('admin', 'index_pdf_list'), $amendmentPDFLink, ['class' => 'amendmentPdfList']) . '</li>
    <li class="secondary">' .
    Html::a(\Yii::t('admin', 'index_export_ods'), $amendmentOdsLink, ['class' => 'amendmentOds']) .
    '</li>';

/*
    <li class="secondary">
    <a href="#ae_excel_export" onClick="$(\'#ae_excel_export\').toggle(); return false;" class="amendmentExcelOpener">
    Export: Änderungsanträge als Excel-Datei</a>
            <ul id="ae_excel_export" style="display: none;">
                <li class="separated">';
echo Html::a('Änderungsantragstext und Begründung getrennt', UrlHelper::createUrl('admin/amendment/excellist'));
echo '</li><li class="combined">';
$url = UrlHelper::createUrl(['admin/amendment/excellist', 'textCombined' => 1]);
echo Html::a('Änderungsantragstext und Begründung in einer Spalte', $url);
echo '</li>
            </ul>
    </li>
*/

echo '</ul>';

/*
<h3>Kommentare</h3>
<ul>
    <li class="secondary">';
echo Html::a('Export: Excel', UrlHelper::createUrl('admin/index/kommentareexcel'));
echo '</li>
</ul>
*/

echo '</div>

    <h2 class="green">' . \Yii::t('admin', 'index_settings') . '</h2>
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
echo '</li>';


echo '<li>' . \Yii::t('admin', 'index_motion_types') . '<ul>';
foreach ($consultation->motionTypes as $motionType) {
    echo '<li>';
    $sectionsUrl = UrlHelper::createUrl(['admin/motion/type', 'motionTypeId' => $motionType->id]);
    echo Html::a($motionType->titlePlural, $sectionsUrl, ['class' => 'motionType' . $motionType->id]);
    echo '</li>';
}
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

echo '</ul></div>';

if (User::currentUserIsSuperuser()) {
    echo '<h2 class="green">' . \Yii::t('admin', 'index_sys_admin') . '</h2>
    <div class="content adminIndex">
    <ul>
    <li>';
    echo Html::a(
        \Yii::t('admin', 'index_site_config'),
        UrlHelper::createUrl('manager/siteconfig'),
        ['class' => 'siteConfigLink']
    );
    echo '</li>
    </ul>';

    echo Html::beginForm('', 'post', ['class' => 'sysadminForm']);
    echo '<button type="submit" name="flushCaches" class="btn btn-small btn-default">' .
        \Yii::t('admin', 'index_flush_caches') . '</button>';
    echo Html::endForm();

    echo '</div>';
}


if (count($todo) > 0) {
    echo '</div><div class="col-md-5">';


    if (count($todo) > 0) {
        echo '<div  class="adminTodo"><h4>' . \Yii::t('admin', 'index_todo') . '</h4>';
        echo '<ul>';
        foreach ($todo as $do) {
            echo '<li class="' . Html::encode($do->todoId) . '">';
            echo '<div class="action">' . Html::encode($do->action) . '</div>';
            echo Html::a($do->title, $do->link);
            if ($do->description) {
                echo '<div class="description">' . Html::encode($do->description) . '</div>';
            }
            echo '</li>';
        }
        echo '</ul></div>';
    }

    echo '</div>';
}


echo '</div>';
