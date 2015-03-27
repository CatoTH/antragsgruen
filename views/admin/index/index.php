<?php
/**
 * @var $this yii\web\View
 * @var array $todoList
 * @var \app\models\db\Site $site
 * @var \app\models\db\Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;

$this->title = 'Administration';
$params->breadcrumbs[] = 'Administration';


use app\components\UrlHelper;
use yii\helpers\Html;

echo '<h1>Administration</h1>';
echo '<div class="content">';

if (count($todoList) > 0) {
    echo "<div  class='adminTodo'><h4>To Do</h4>";
    echo "<ul>";
    foreach ($todoList as $do) {
        echo "<li>" . Html::a($do[0], $do[1]) . "</li>";
    }
    echo "</ul></div>";
}


echo '<h4>Administration</h4>
    <ul>
        <li style="font-weight: bold;">';

$link = UrlHelper::createUrl('admin/index/consultation');
echo Html::a('Diese Veranstaltung / Programmdiskussion', $link, ['id' => 'consultationLink']);

echo '</li><li style="margin-left: 20px;">';
echo Html::a("ExpertInnen-Einstellungen", UrlHelper::createUrl('admin/index/consultationextended'));
echo '</li><li style="margin-top: 10px; font-weight: bold;">';
echo Html::a('Anträge', UrlHelper::createUrl('admin/antraege'));
echo '</li><li style="margin-left: 20px;">';
echo Html::a('Abschnitte festlegen', UrlHelper::createUrl('admin/motion/sections'));
echo '</li><li style="margin-left: 20px;">';

$motionp = $consultation->getMotionPolicy();
if ($motionp->checkCurUserHeuristically()) {
    echo Html::a('Neuen Antrag anlegen', UrlHelper::createUrl('motion/create'));
} else {
    echo 'Neuen Antrag anlegen: <em>' . $motionp->getPermissionDeniedMsg($consultation->getWording()) . '</em>';
}
echo '</li>

        <li style="margin-left: 20px;">
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

        <li style="margin-top: 10px; font-weight: bold;">';
echo Html::a('Änderungsanträge', UrlHelper::createUrl('admin/aenderungsantraege'));
echo '</li><li style="margin-left: 20px;">';
echo Html::a('Liste aller PDFs', UrlHelper::createUrl('admin/index/aePDFList'));
echo '</li><li style="margin-left: 20px;">
            <a href="#ae_excel_export" onClick="$(\'#ae_excel_export\').toggle(); return false;">
            Export: Änderungsanträge als Excel-Datei</a>
            <ul id="ae_excel_export" style="display: none;">
                <li>';
echo Html::a('Änderungsantragstext und Begründung getrennt', UrlHelper::createUrl('admin/index/aeExcelList'));
echo '</li><li>';
$url = UrlHelper::createUrl(['admin/index/aeExcelList', 'text_begruendung_zusammen' => 1]);
echo Html::a('Änderungsantragstext und Begründung in einer Spalte', $url);
echo '</li><li>';
$url = UrlHelper::createUrl(['admin/index/aeExcelList', 'antraege_separat' => 1]);
echo Html::a('Texte getrennt, Antragsnummer als separate Spalte', $url);
echo '</li>
            </ul>
        </li>

        <li style="margin-left: 20px;">
            <a href="#ae_ods_export" onClick="$(\'#ae_ods_export\').toggle(); return false;">
            Export: Anträge als Tabelle (OpenOffice)</a>
            <ul id="ae_ods_export" style="display: none;">
                <li>';
echo Html::a('Antragstext und Begründung getrennt', UrlHelper::createUrl('admin/index/aeOdsList'));
echo '</li><li>';
$url = UrlHelper::createUrl(['admin/index/aeOdsList', 'text_begruendung_zusammen' => 1]);
echo Html::a('Antragstext und Begründung in einer Spalte', $url);
echo '</li>
            </ul>
        </li>

        <li style="margin-top: 10px;">';
echo Html::a('Export: Kommentare als Excel-Datei', UrlHelper::createUrl('admin/index/kommentareexcel'));
echo '</li><li style="margin-top: 10px;">';
echo Html::a('Redaktionelle Texte', UrlHelper::createUrl('admin/texte'));
echo '</li></ul>

    <br><br><br>

    <h4>Veranstaltungsreihe / Subdomain</h4>
    <ul>
        <li>';
echo Html::a('Weitere Admins', UrlHelper::createUrl('admin/index/admins'), ['id' => 'adminsManageLink']);
echo '</li><li>';
echo Html::a('Weitere Veranstaltungen anlegen / verwalten', UrlHelper::createUrl('admin/index/reiheVeranstaltungen'));
echo '</li><li>';
echo Html::a('Veranstaltungsreihen-BenutzerInnen', UrlHelper::createUrl('admin/index/namespacedAccounts'));
echo '</li>
    </ul>
</div>';
