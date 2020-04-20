<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var bool $adminEdit
 */

/** @var Motion[] $replacedByMotions */
$replacedByMotions = [];
foreach ($motion->replacedByMotions as $replMotion) {
    if (!in_array($replMotion->status, $motion->getMyConsultation()->getInvisibleMotionStatuses())) {
        $replacedByMotions[] = $replMotion;
    }
}


$html        = '<ul class="sidebarActions" aria-label="' . Html::encode(Yii::t('motion', 'sidebar_title_aria')) . '">';
$sidebarRows = 0;

try {
    $motion->isCurrentlyAmendable(true, true, true);

    $html .= '<li class="amendmentCreate">';
    $amendCreateUrl = UrlHelper::createUrl(['amendment/create', 'motionSlug' => $motion->getMotionSlug()]);
    $title          = '<span class="icon glyphicon glyphicon-flash" aria-hidden="true"></span>';
    $title .= Yii::t('motion', 'amendment_create');
    if (!$motion->isCurrentlyAmendable(false, true)) {
        $title .= ' <span class="onlyAdmins">(' . Yii::t('motion', 'amendment_create_admin') . ')</span>';
    }
    $html .= Html::a($title, $amendCreateUrl, ['rel' => 'nofollow']) . '</li>';
    $layout->menusSmallAttachment = '<a class="navbar-brand" href="' . Html::encode($amendCreateUrl) . '" ' .
        'rel="nofollow">' . $title . '</a>';
    $sidebarRows++;
} catch (\app\models\exceptions\NotAmendable $e) {
    if ($e->isMessagePublic()) {
        $createLi = '<li class="amendmentCreate deactivated">';
        $createLi .= '<span><span class="icon glyphicon glyphicon-flash" aria-hidden="true"></span>';
        $createLi .= Html::encode(Yii::t('motion', 'amendment_create'));
        $createLi .= '<br><span class="deactivatedMsg">';
        $createLi .= Html::encode($e->getMessage()) . '</span></span></li>';

        $html .= $createLi;
        $layout->menusHtmlSmall[] = $createLi;

        $sidebarRows++;
    }
}

if ($motion->motionType->getPDFLayoutClass() !== null && $motion->isVisible()) {
    $pdfLi = '<li class="download">';
    $title = '<span class="icon glyphicon glyphicon-download-alt" aria-hidden="true"></span>' .
        Yii::t('motion', 'pdf_version');
    $pdfLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'pdf')) . '</li>';
    $html .= $pdfLi;
    $layout->menusHtmlSmall[] = $pdfLi;
    $sidebarRows++;
}

if ($motion->canMergeAmendments()) {
    $mergeLi = '<li class="mergeamendments">';
    $title   = (count($motion->getVisibleAmendments(false)) > 0 ? 'amendments_merge' : 'amendments_merge_noamend');
    $title   = '<span class="icon glyphicon glyphicon-scissors" aria-hidden="true"></span>' .
        Yii::t('motion', $title);
    $mergeLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'merge-amendments-init')) . '</li>';
    $html .= $mergeLi;
    $layout->menusHtmlSmall[] = $mergeLi;
    $sidebarRows++;
}

if ($motion->canEdit()) {
    $editLi = '<li class="edit">';
    $title  = '<span class="icon glyphicon glyphicon-edit" aria-hidden="true"></span>' .
        str_replace('%TYPE%', Html::encode($motion->motionType->titleSingular), Yii::t('motion', 'motion_edit'));
    $editLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'edit')) . '</li>';
    $html .= $editLi;
    $layout->menusHtmlSmall[] = $editLi;
    $sidebarRows++;
}

if ($motion->canWithdraw()) {
    $withdrawLi = '<li class="withdraw">';
    $title      = '<span class="icon glyphicon glyphicon-remove" aria-hidden="true"></span>' .
        str_replace('%TYPE%', Html::encode($motion->motionType->titleSingular), Yii::t('motion', 'motion_withdraw'));
    $withdrawLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'withdraw')) . '</li>';
    $html .= $withdrawLi;
    $layout->menusHtmlSmall[] = $withdrawLi;
    $sidebarRows++;
}

if ($adminEdit) {
    $adminLi = '<li class="adminEdit">';
    $title   = '<span class="icon glyphicon glyphicon-wrench" aria-hidden="true"></span>' . Yii::t('motion', 'motion_admin_edit');
    $adminLi .= Html::a($title, $adminEdit) . '</li>';
    $html .= $adminLi;
    $layout->menusHtmlSmall[] = $adminLi;
    $sidebarRows++;
}

if (!$motion->getMyConsultation()->getForcedMotion()) {
    $html .= '<li class="back">';
    $title = '<span class="icon glyphicon glyphicon-chevron-left" aria-hidden="true"></span>' . Yii::t('motion', 'back_start');
    $html .= Html::a($title, UrlHelper::homeUrl()) . '</li>';
    $sidebarRows++;
}

$html .= '</ul>';


if ($sidebarRows > 0) {
    $layout->menusHtml[] = $html;
}


return $sidebarRows;
