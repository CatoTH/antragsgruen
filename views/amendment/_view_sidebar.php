<?php

use app\components\UrlHelper;
use app\models\settings\Layout;
use yii\helpers\Html;

/**
 * @var \app\models\db\Amendment $amendment
 * @var bool $adminEdit
 * @var Layout $layout
 */

$motionType   = $amendment->getMyMotionType();
$html        = '<ul class="sidebarActions" aria-label="' . Html::encode(Yii::t('amend', 'sidebar_title_aria')) . '">';
$sidebarRows = 0;

if ($motionType->getPDFLayoutClass() !== null && $amendment->isVisible()) {
    $html .= '<li class="download">';
    $title = '<span class="icon glyphicon glyphicon-download-alt" aria-hidden="true"></span>' .
        Yii::t('motion', 'download_pdf');
    $html .= Html::a($title, UrlHelper::createAmendmentUrl($amendment, 'pdf')) . '</li>';
    $sidebarRows++;
}


if ($amendment->canEditText()) {
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

if ($amendment->getMyMotionType()->getSettingsObj()->allowAmendmentsToAmendments) {
    try {
        $amendment->getMyMotion()->isCurrentlyAmendable(true, true, true);

        $html .= '<li class="amendmentCreate">';
        $amendCreateUrl = UrlHelper::createUrl([
            'amendment/create',
            'motionSlug' => $amendment->getMyMotion()->getMotionSlug(),
            'createFromAmendment' => $amendment->id,
        ]);
        $title          = '<span class="icon glyphicon glyphicon-flash" aria-hidden="true"></span>';
        $title .= Yii::t('motion', 'amendment_create_based_on_amend');
        if (!$amendment->getMyMotion()->isCurrentlyAmendable(false, true)) {
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
            $createLi .= Html::encode(Yii::t('motion', 'amendment_create_based_on_amend'));
            $createLi .= '<br><span class="deactivatedMsg">';
            $createLi .= Html::encode($e->getMessage()) . '</span></span></li>';

            $html .= $createLi;
            $layout->menusHtmlSmall[] = $createLi;

            $sidebarRows++;
        }
    }
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
if ($motionType->amendmentsOnly) {
    $title = '<span class="icon glyphicon glyphicon-chevron-left" aria-hidden="true"></span>' . Yii::t('motion', 'back_start');
    $html .= Html::a($title, UrlHelper::homeUrl()) . '</li>';
} else {
    $title = '<span class="icon glyphicon glyphicon-chevron-left" aria-hidden="true"></span>' . Yii::t('amend', 'sidebar_back');
    $html .= Html::a($title, UrlHelper::createMotionUrl($amendment->getMyMotion())) . '</li>';
}
$sidebarRows++;

$html .= '</ul>';


$layout->menusHtml[] = $html;
$layout->menuSidebarType = Layout::SIDEBAR_TYPE_AMENDMENT;


return $sidebarRows;
