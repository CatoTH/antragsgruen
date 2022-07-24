<?php

use app\components\UrlHelper;
use app\models\db\ConsultationUserGroup;
use app\models\settings\Layout;
use yii\helpers\Html;

/**
 * @var string $sidebarMode
 * @var Layout $layout
 * @var \app\models\db\Consultation $consultation
 */

$html        = '<ul class="sidebarActions" aria-label="' . Html::encode(Yii::t('motion', 'sidebar_title_aria')) . '">';

$pdfLi = '<li class="votings">';
if ($sidebarMode === 'open') {
    $title = '<span class="icon glyphicon glyphicon-ok" aria-hidden="true"></span>' . Yii::t('voting', 'sidebar_open');
    $pdfLi .= Html::a($title, UrlHelper::createUrl('/consultation/votings'), ['class' => 'active']) . '</li>';
} else {
    $title = '<span class="icon icon-placeholder" aria-hidden="true"></span>' . Yii::t('voting', 'sidebar_open');
    $pdfLi .= Html::a($title, UrlHelper::createUrl('/consultation/votings')) . '</li>';
}
$html .= $pdfLi;
$layout->menusHtmlSmall[] = $pdfLi;


$pdfLi = '<li class="results">';
$title = Yii::t('voting', 'sidebar_results');
if ($sidebarMode === 'results') {
    $title = '<span class="icon glyphicon glyphicon-ok" aria-hidden="true"></span>' . Yii::t('voting', 'sidebar_results');
    $pdfLi .= Html::a($title, UrlHelper::createUrl('/consultation/voting-results'), ['class' => 'active']) . '</li>';
} else {
    $title = '<span class="icon icon-placeholder" aria-hidden="true"></span>' . Yii::t('voting', 'sidebar_results');
    $pdfLi .= Html::a($title, UrlHelper::createUrl('/consultation/voting-results')) . '</li>';
}
$html .= $pdfLi;
$layout->menusHtmlSmall[] = $pdfLi;


if (\app\models\db\User::havePrivilege($consultation, ConsultationUserGroup::PRIVILEGE_VOTINGS)) {
    $pdfLi = '<li class="admin">';
    $title = Yii::t('voting', 'sidebar_admin');
    if ($sidebarMode === 'admin') {
        $title = '<span class="icon glyphicon glyphicon-ok" aria-hidden="true"></span>' . Yii::t('voting', 'sidebar_admin');
        $pdfLi .= Html::a($title, UrlHelper::createUrl('/consultation/admin-votings'), ['class' => 'active']) . '</li>';
    } else {
        $title = '<span class="icon icon-placeholder" aria-hidden="true"></span>' . Yii::t('voting', 'sidebar_admin');
        $pdfLi .= Html::a($title, UrlHelper::createUrl('/consultation/admin-votings')) . '</li>';
    }
    $html .= $pdfLi;
    $layout->menusHtmlSmall[] = $pdfLi;
}

$html .= '</ul>';
$layout->menusHtml[] = $html;
