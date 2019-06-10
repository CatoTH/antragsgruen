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

try {
    $amendment->isCurrentlyAmendable(true, true, true);

    $html .= '<li class="amendmentCreate">';
    $amendCreateUrl = UrlHelper::createAmendmentUrl($amendment, 'amend');

    $title          = '<span class="icon glyphicon glyphicon-flash"></span>';
    $title .= \Yii::t('motion', 'amendment_create');
    if (!$amendment->isCurrentlyAmendable(false, true)) {
        $title .= ' <span class="onlyAdmins">(' . \Yii::t('motion', 'amendment_create_admin') . ')</span>';
    }
    $html .= Html::a($title, $amendCreateUrl, ['rel' => 'nofollow']) . '</li>';
    $layout->menusSmallAttachment = '<a class="navbar-brand" href="' . Html::encode($amendCreateUrl) . '" ' .
        'rel="nofollow">' . $title . '</a>';
    $sidebarRows++;
} catch (\app\models\exceptions\NotAmendable $e) {
    if ($e->isMessagePublic()) {
        $createLi = '<li class="amendmentCreate">';
        $createLi .= '<span style="font-style: italic;"><span class="icon glyphicon glyphicon-flash"></span>';
        $createLi .= Html::encode(Yii::t('motion', 'amendment_create'));
        $createLi .= '<br><span style="font-size: 13px; color: #dbdbdb; text-transform: none;">';
        $createLi .= Html::encode($e->getMessage()) . '</span></span></li>';

        $html .= $createLi;
        $layout->menusHtmlSmall[] = $createLi;

        $sidebarRows++;
    }
}

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
