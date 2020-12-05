<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var \app\models\db\Amendment $amendment
 * @var bool $adminEdit
 * @var \app\models\settings\Layout $layout
 */

$html        = '<ul class="sidebarActions" aria-label="' . Html::encode(Yii::t('amend', 'sidebar_title_aria')) . '">';
$sidebarRows = 0;

if ($amendment->getMyMotion()->motionType->getPDFLayoutClass() !== null && $amendment->isVisible()) {
    $html .= '<li class="download">';
    $title = '<span class="icon glyphicon glyphicon-download-alt" aria-hidden="true"></span>' .
        Yii::t('motion', 'download_pdf');
    $html .= Html::a($title, UrlHelper::createAmendmentUrl($amendment, 'pdf')) . '</li>';
    $sidebarRows++;
}


if ($amendment->canEdit()) {
    $html .= '<li class="edit">';
    $title = '<span class="icon glyphicon glyphicon-edit" aria-hidden="true"></span>' .
        Yii::t('amend', 'amendment_edit');
    $html .= Html::a($title, UrlHelper::createAmendmentUrl($amendment, 'edit')) . '</li>';
    $sidebarRows++;
}

if ($amendment->canWithdraw()) {
    $html .= '<li class="withdraw">';
    $title = '<span class="icon glyphicon glyphicon-remove" aria-hidden="true"></span>' .
        Yii::t('amend', 'amendment_withdraw');
    $html .= Html::a($title, UrlHelper::createAmendmentUrl($amendment, 'withdraw')) . '</li>';
    $sidebarRows++;
}

if ($amendment->canMergeIntoMotion(true)) {
    $html .= '<li class="mergeIntoMotion">';
    $title = '<span class="icon glyphicon glyphicon-wrench" aria-hidden="true"></span>' . Yii::t('amend', 'sidebar_mergeintomotion');
    $url = UrlHelper::createAmendmentUrl($amendment, 'merge');
    $html .= Html::a($title, $url) . '</li>';
    $sidebarRows++;
}

if ($adminEdit) {
    $html .= '<li class="adminEdit">';
    $title = '<span class="icon glyphicon glyphicon-wrench" aria-hidden="true"></span>' . Yii::t('amend', 'sidebar_adminedit');
    $html .= Html::a($title, $adminEdit) . '</li>';
    $sidebarRows++;
}

$html .= '<li class="back">';
$title = '<span class="icon glyphicon glyphicon-chevron-left" aria-hidden="true"></span>' . Yii::t('amend', 'sidebar_back');
$html .= Html::a($title, UrlHelper::createMotionUrl($amendment->getMyMotion())) . '</li>';
$sidebarRows++;

$html .= '</ul>';


$layout->menusHtml[] = $html;


return $sidebarRows;
