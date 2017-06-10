<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use app\views\motion\LayoutHelper;
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var bool $adminEdit
 */

/** @var Motion[] $replacedByMotions */
$replacedByMotions = [];
foreach ($motion->replacedByMotions as $replMotion) {
    if (!in_array($replMotion->status, $motion->getMyConsultation()->getInvisibleMotionStati())) {
        $replacedByMotions[] = $replMotion;
    }
}


$html        = '<ul class="sidebarActions">';
$sidebarRows = 0;

try {
    $motion->isCurrentlyAmendable(true, true, true);

    $html .= '<li class="amendmentCreate">';
    $amendCreateUrl = UrlHelper::createUrl(['amendment/create', 'motionSlug' => $motion->getMotionSlug()]);
    $title          = '<span class="icon glyphicon glyphicon-flash"></span>';
    $title .= \Yii::t('motion', 'amendment_create');
    if (!$motion->isCurrentlyAmendable(false, true)) {
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

if ($motion->motionType->getPDFLayoutClass() !== null && $motion->isVisible()) {
    $pdfLi = '<li class="download">';
    $title = '<span class="icon glyphicon glyphicon-download-alt"></span>' .
        \Yii::t('motion', 'pdf_version');
    $pdfLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'pdf')) . '</li>';
    $html .= $pdfLi;
    $layout->menusHtmlSmall[] = $pdfLi;
    $sidebarRows++;
}

if ($motion->canMergeAmendments()) {
    $mergeLi = '<li class="mergeamendments">';
    $title   = (count($motion->getVisibleAmendments(false)) > 0 ? 'amendments_merge' : 'amendments_merge_noamend');
    $title   = '<span class="icon glyphicon glyphicon-scissors"></span>' .
        Yii::t('motion', $title);
    $mergeLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'merge-amendments-init')) . '</li>';
    $html .= $mergeLi;
    $layout->menusHtmlSmall[] = $mergeLi;
    $sidebarRows++;
}

if ($motion->canEdit()) {
    $editLi = '<li class="edit">';
    $title  = '<span class="icon glyphicon glyphicon-edit"></span>' .
        str_replace('%TYPE%', $motion->motionType->titleSingular, \Yii::t('motion', 'motion_edit'));
    $editLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'edit')) . '</li>';
    $html .= $editLi;
    $layout->menusHtmlSmall[] = $editLi;
    $sidebarRows++;
}

if ($motion->canWithdraw()) {
    $withdrawLi = '<li class="withdraw">';
    $title      = '<span class="icon glyphicon glyphicon-remove"></span>' .
        str_replace('%TYPE%', $motion->motionType->titleSingular, \Yii::t('motion', 'motion_withdraw'));
    $withdrawLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'withdraw')) . '</li>';
    $html .= $withdrawLi;
    $layout->menusHtmlSmall[] = $withdrawLi;
    $sidebarRows++;
}

if ($adminEdit) {
    $adminLi = '<li class="adminEdit">';
    $title   = '<span class="icon glyphicon glyphicon-wrench"></span>' . \Yii::t('motion', 'motion_admin_edit');
    $adminLi .= Html::a($title, $adminEdit) . '</li>';
    $html .= $adminLi;
    $layout->menusHtmlSmall[] = $adminLi;
    $sidebarRows++;
}

if (!$motion->getMyConsultation()->getForcedMotion()) {
    $html .= '<li class="back">';
    $title = '<span class="icon glyphicon glyphicon-chevron-left"></span>' . \Yii::t('motion', 'back_start');
    $html .= Html::a($title, UrlHelper::homeUrl()) . '</li>';
    $sidebarRows++;
}

$html .= '</ul>';

if ($motion->isSocialSharable() && count($replacedByMotions) == 0) {
    $myUrl          = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
    $dataTitle      = $motion->getTitleWithPrefix();
    $html .= '</div><div class="hidden-xs">' . LayoutHelper::getShareButtons($myUrl, $dataTitle);
    $sidebarRows++;
}


if ($sidebarRows > 0) {
    $layout->menusHtml[] = $html;
}


return $sidebarRows;
