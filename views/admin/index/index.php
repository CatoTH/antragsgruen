<?php

use app\components\UrlHelper;
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

$this->title = 'Administration';
$layout->addCSS('/css/backend.css');
$layout->addBreadcrumb('Administration');


echo '<h1>Administration</h1>';


if (count($todo) > 0) {
    echo '<div class="row" style="margin-top: 20px;">';
    echo '<div class="col-md-7">';
} else {
    echo '<div style="margin-top: 20px;">';
}


echo '<h2 class="green">' . 'Anträge / Dokumente' . '</h2>
    <div class="content adminIndex">';

echo '<h3>' . Html::a(
        'Alle Anträge und Änderungsanträge',
        UrlHelper::createUrl('admin/motion/listall'),
        ['class' => 'motionListAll']
    ) . '</h3>';

// echo Html::a('Anträge', UrlHelper::createUrl('admin/motion/index'), ['class' => 'motionIndex']);

foreach ($consultation->motionTypes as $motionType) {
    echo '<h3>' . Html::encode($motionType->titlePlural) . '</h3><ul>';

    $motionp = $motionType->getMotionPolicy();
    if ($motionp->checkCurUserHeuristically()) {
        $createUrl = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionType->id]);
        echo '<li>' . Html::a('Neu anlegen', $createUrl) . '</li>';
    } else {
        echo '<li>Neu anlegen: <em>' . $motionp->getPermissionDeniedMotionMsg() . '</em></li>';
    }

    $odsUrl = UrlHelper::createUrl(['admin/motion/odslist', 'motionTypeId' => $motionType->id]);
    echo '<li class="secondary">';
    echo Html::a('Export: Spreadsheet', $odsUrl, ['class' => 'motionODS' . $motionType->id]) . '</li>';

    $excelUrl = UrlHelper::createUrl(['admin/motion/excellist', 'motionTypeId' => $motionType->id]);
    echo '<li class="secondary">';
    echo Html::a('Export: Excel', $excelUrl, ['class' => 'motionExcel' . $motionType->id]) . ' (fehleranfälliger)</li>';

    echo '</ul>';
}
echo '
<!--
        <li>
            <a href="#antrag_excel_export" onClick="$(\'#antrag_excel_export\').toggle(); return false;">
            Export: Anträge als Excel-Datei</a>
            <ul id="antrag_excel_export" style="display: none;">
                <li>';
echo Html::a('Antragstext und Begründung getrennt', UrlHelper::createUrl('admin/index/antragExcelList'));
echo '</li><li>';
$url = UrlHelper::createUrl(['admin/index/antragExcelList', 'text_begruendung_zusammen' => 1]);
echo Html::a('Antragstext und Begründung in einer Spalte', $url);
echo '</li>
            </ul>
        </li>
-->';

//echo Html::a('Änderungsanträge', UrlHelper::createUrl('admin/aenderungsantraege'));
$amendmentOdsLink   = UrlHelper::createUrl('admin/amendment/odslist');
$amendmentPDFLink = UrlHelper::createUrl('admin/amendment/pdflist');
echo '<h3>Änderungsanträge</h3>
<ul>
    <li>' .
    Html::a('Sammel-PDF', UrlHelper::createUrl('amendment/pdfcollection'), ['class' => 'amendmentsPdf']) .
    '</li>
    <li class="secondary">' . Html::a('Liste aller PDFs', $amendmentPDFLink, ['class' => 'amendmentPdfList']) . '</li>
        <li>' .
    Html::a('Export: OpenOffice-Spreadsheet', $amendmentOdsLink, ['class' => 'amendmentOds']) .
    '</li>

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
</ul>


<h3>Kommentare</h3>
<ul>
    <li class="secondary">';
echo Html::a('Export: Excel', UrlHelper::createUrl('admin/index/kommentareexcel'));
echo '</li>
</ul>


</div>



    <h2 class="green">' . 'Einstellungen' . '</h2>
    <div class="content adminIndex">
    <ul>
    <li>';

$link = UrlHelper::createUrl('admin/index/consultation');
echo Html::a('Diese Veranstaltung / Programmdiskussion', $link, ['id' => 'consultationLink']);

echo '</li><li class="secondary">';
echo Html::a(
    "ExpertInnen-Einstellungen",
    UrlHelper::createUrl('admin/index/consultationextended'),
    ['id' => 'consultationextendedLink']
);
echo '</li>';

echo '<li class="secondary">';
echo Html::a(
    Yii::t('backend', 'Translation / Wording'),
    UrlHelper::createUrl('admin/index/translation'),
    ['id' => 'translationLink']
);
echo '</li>';


echo '<li>Antragstypen bearbeiten<ul>';
foreach ($consultation->motionTypes as $motionType) {
    echo '<li>';
    $sectionsUrl = UrlHelper::createUrl(['admin/motion/type', 'motionTypeId' => $motionType->id]);
    echo Html::a($motionType->titlePlural, $sectionsUrl, ['class' => 'motionType' . $motionType->id]);
    echo '</li>';
}
echo '</ul></li>';

echo '<li>';
echo Html::a(
    'Login / BenutzerInnen / Admins',
    UrlHelper::createUrl('admin/index/siteaccess'),
    ['class' => 'siteAccessLink']
);
echo '</li><li>';
echo Html::a('Weitere Veranstaltungen anlegen / verwalten', UrlHelper::createUrl('admin/index/reiheVeranstaltungen'));
echo '</li>
    </ul></div>';

if (count($todo) > 0) {
    echo '</div><div class="col-md-5">';


    if (count($todo) > 0) {
        echo '<div  class="adminTodo"><h4>To Do</h4>';
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
