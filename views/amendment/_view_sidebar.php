<?php

use app\components\{HTMLTools, UrlHelper};
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
$menusHtmlSmall = [];

if ($motionType->hasPdfLayout() && $amendment->isVisible()) {
    $downloadLi = '<li class="download">';
    $title = '<span class="icon glyphicon glyphicon-download-alt" aria-hidden="true"></span>' .
        Yii::t('motion', 'download_pdf');
    $downloadLi .= HtmlTools::createExternalLink($title, UrlHelper::createAmendmentUrl($amendment, 'pdf')) . '</li>';

    $html .= $downloadLi;
    $menusHtmlSmall[] = $downloadLi;
    $sidebarRows++;
}


if ($amendment->canEditText()) {
    $editLi = '<li class="edit">';
    $title = '<span class="icon glyphicon glyphicon-edit" aria-hidden="true"></span>' .
        Yii::t('amend', 'amendment_edit');
    $editLi .= Html::a($title, UrlHelper::createAmendmentUrl($amendment, 'edit')) . '</li>';

    $html .= $editLi;
    $menusHtmlSmall[] = $editLi;
    $sidebarRows++;
}

if ($amendment->canWithdraw()) {
    $withdrawLi = '<li class="withdraw">';
    $title = '<span class="icon glyphicon glyphicon-remove" aria-hidden="true"></span>' .
        Yii::t('amend', 'amendment_withdraw');
    $withdrawLi .= Html::a($title, UrlHelper::createAmendmentUrl($amendment, 'withdraw')) . '</li>';

    $html .= $withdrawLi;
    $menusHtmlSmall[] = $withdrawLi;

    $sidebarRows++;
}

if ($amendment->getMyMotionType()->getSettingsObj()->allowAmendmentsToAmendments) {
    try {
        $amendment->getMyMotion()->isCurrentlyAmendable(true, true, true);

        $createLi = '<li class="amendmentCreate">';
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
        $createLi .= Html::a($title, $amendCreateUrl, ['rel' => 'nofollow']) . '</li>';

        $html .= $createLi;
        $menusHtmlSmall[] = $createLi;
        $sidebarRows++;
    } catch (\app\models\exceptions\NotAmendable $e) {
        if ($e->isMessagePublic()) {
            $createLi = '<div class="amendmentCreate deactivated">';
            $createLi .= '<span><span class="icon glyphicon glyphicon-flash" aria-hidden="true"></span>';
            $createLi .= Html::encode(Yii::t('motion', 'amendment_create_based_on_amend'));
            $createLi .= '<br><span class="deactivatedMsg">';
            $createLi .= Html::encode($e->getMessage()) . '</span></span></div>';

            $html .= $createLi;
            $menusHtmlSmall[] = $createLi;

            $sidebarRows++;
        }
    }
}

if ($amendment->canMergeIntoMotion(true)) {
    $mergeLi = '<li class="mergeIntoMotion">';
    $title = '<span class="icon glyphicon glyphicon-wrench" aria-hidden="true"></span>' . Yii::t('amend', 'sidebar_mergeintomotion');
    $url = UrlHelper::createAmendmentUrl($amendment, 'merge');
    $mergeLi .= Html::a($title, $url) . '</li>';

    $html .= $mergeLi;
    $menusHtmlSmall[] = $mergeLi;
    $sidebarRows++;
}

if ($adminEdit) {
    $adminEditLi = '<li class="adminEdit">';
    $title = '<span class="icon glyphicon glyphicon-wrench" aria-hidden="true"></span>' . Yii::t('amend', 'sidebar_adminedit');
    $adminEditLi .= Html::a($title, $adminEdit) . '</li>';

    $html .= $adminEditLi;
    $menusHtmlSmall[] = $adminEditLi;
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
$layout->menusHtmlSmall[] = '<ul>' . implode("\n", $menusHtmlSmall) . '</ul>';
$layout->menuSidebarType = Layout::SIDEBAR_TYPE_AMENDMENT;


return $sidebarRows;
