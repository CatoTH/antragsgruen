<?php

use app\components\UrlHelper;
use yii\helpers\Html;
use app\views\motion\LayoutHelper as MotionLayoutHelper;

/**
 * @var \app\models\db\Amendment $amendment
 * @var bool $adminEdit
 */

$html        = '<ul class="sidebarActions">';
$sidebarRows = 0;

if ($amendment->getMyMotion()->motionType->getPDFLayoutClass() !== null && $amendment->isVisible()) {
    $html .= '<li class="download">';
    $title = '<span class="icon glyphicon glyphicon-download-alt"></span>' .
        Yii::t('motion', 'download_pdf');
    $html .= Html::a($title, UrlHelper::createAmendmentUrl($amendment, 'pdf')) . '</li>';
    $sidebarRows++;
}


if ($amendment->canEdit()) {
    $html .= '<li class="edit">';
    $title = '<span class="icon glyphicon glyphicon-edit"></span>' .
        Yii::t('amend', 'amendment_edit');
    $html .= Html::a($title, UrlHelper::createAmendmentUrl($amendment, 'edit')) . '</li>';
    $sidebarRows++;
}

if ($amendment->canWithdraw()) {
    $html .= '<li class="withdraw">';
    $title = '<span class="icon glyphicon glyphicon-remove"></span>' .
        Yii::t('amend', 'amendment_withdraw');
    $html .= Html::a($title, UrlHelper::createAmendmentUrl($amendment, 'withdraw')) . '</li>';
    $sidebarRows++;
}

if ($amendment->canMergeIntoMotion(true)) {
    $html .= '<li class="mergeIntoMotion">';
    $title = '<span class="icon glyphicon glyphicon-wrench"></span>' . \Yii::t('amend', 'sidebar_mergeintomotion');
    $url = UrlHelper::createAmendmentUrl($amendment, 'merge');
    $html .= Html::a($title, $url) . '</li>';
    $sidebarRows++;
}

if ($adminEdit) {
    $html .= '<li class="adminEdit">';
    $title = '<span class="icon glyphicon glyphicon-wrench"></span>' . \Yii::t('amend', 'sidebar_adminedit');
    $html .= Html::a($title, $adminEdit) . '</li>';
    $sidebarRows++;
}

$html .= '<li class="back">';
$title = '<span class="icon glyphicon glyphicon-chevron-left"></span>' . \Yii::t('amend', 'sidebar_back');
$html .= Html::a($title, UrlHelper::createMotionUrl($amendment->getMyMotion())) . '</li>';
$sidebarRows++;

$html .= '</ul>';

if ($amendment->isSocialSharable()) {
    $myUrl          = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment));
    $dataTitle      = $amendment->getTitle();
    $html .= '</div><div class="hidden-xs">' . MotionLayoutHelper::getShareButtons($myUrl, $dataTitle) . '</div>';
}


$layout->menusHtml[] = $html;


return $sidebarRows;
