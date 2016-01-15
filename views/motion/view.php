<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\User;
use app\models\forms\CommentForm;
use app\models\policies\IPolicy;
use app\models\policies\Nobody;
use app\views\motion\LayoutHelper;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var int[] $openedComments
 * @var string|null $adminEdit
 * @var null|string $supportStatus
 * @var null|CommentForm $commentForm
 * @var bool $commentWholeMotions
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

if (isset($_REQUEST['backUrl']) && $_REQUEST['backTitle']) {
    $layout->addBreadcrumb($_REQUEST['backTitle'], $_REQUEST['backUrl']);
}
$layout->addBreadcrumb($motion->motionType->titleSingular);

$this->title = $motion->getTitleWithPrefix() . ' (' . $motion->getConsultation()->title . ', Antragsgrün)';


$html        = '<ul class="sidebarActions">';
$sidebarRows = 0;

$policy = $motion->motionType->getAmendmentPolicy();
if ($policy->checkCurrUserAmendment(true, true)) {
    $html .= '<li class="amendmentCreate">';
    $amendCreateUrl = UrlHelper::createUrl(['amendment/create', 'motionId' => $motion->id]);
    $title          = '<span class="icon glyphicon glyphicon-flash"></span>';
    $title .= \Yii::t('motion', 'amendment_create');
    $html .= Html::a($title, $amendCreateUrl) . '</li>';
    $layout->menusSmallAttachment = '<a class="navbar-brand" href="' . Html::encode($amendCreateUrl) . '">' .
        $title . '</a>';
    $sidebarRows++;
} elseif ($policy->getPolicyID() != IPolicy::POLICY_NOBODY) {
    $msg = $policy->getPermissionDeniedAmendmentMsg();
    if ($msg != '') {
        $createLi = '<li class="amendmentCreate">';
        $createLi .= '<span style="font-style: italic;"><span class="icon glyphicon glyphicon-flash"></span>';
        $createLi .= Html::encode(Yii::t('motion', 'amendment_create'));
        $createLi .= '<br><span style="font-size: 13px; color: #dbdbdb; text-transform: none;">';
        $createLi .= Html::encode($msg) . '</span></span></li>';

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
    $mergeLi .= Html::a($title, UrlHelper::createMotionUrl($motion, 'mergeamendments')) . '</li>';
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

$html .= '<li class="back">';
$title = '<span class="icon glyphicon glyphicon-chevron-left"></span>' . \Yii::t('motion', 'back_start');
$html .= Html::a($title, UrlHelper::createUrl('consultation/index')) . '</li>';
$sidebarRows++;

$html .= '</ul>';
$layout->menusHtml[] = $html;

$minimalisticUi = $motion->getConsultation()->getSettings()->minimalisticUI;
$minHeight      = $sidebarRows * 40 - 60;

echo '<h1>' . Html::encode($motion->getTitleWithPrefix()) . '</h1>';

echo $layout->getMiniMenu('motionSidebarSmall');

echo '<div class="motionData" style="min-height: ' . $minHeight . 'px;">';

if (!$minimalisticUi) {
    include(__DIR__ . DIRECTORY_SEPARATOR . 'view_motiondata.php');
}

echo $controller->showErrors();

echo '</div>';

$main = $right = '';
foreach ($motion->getSortedSections(true) as $i => $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }
    if ($section->isLayoutRight() && $motion->motionType->layoutTwoCols) {
        $right .= '<section class="sectionType' . $section->getSettings()->type . '">';
        $right .= $section->getSectionType()->getSimple(true);
        $right .= '</section>';
    } else {
        $main .= '<section class="motionTextHolder sectionType' . $section->getSettings()->type;
        if ($motion->getConsultation()->getSettings()->lineLength > 80) {
            $main .= ' smallFont';
        }
        $main .= ' motionTextHolder' . $i . '" id="section_' . $section->sectionId . '">';
        if ($section->getSettings()->type != \app\models\sectionTypes\PDF::TYPE_PDF &&
            $section->getSettings()->type != \app\models\sectionTypes\PDF::TYPE_IMAGE
        ) {
            $main .= '<h3 class="green">' . Html::encode($section->getSettings()->title) . '</h3>';
        }

        $commOp = (isset($openedComments[$section->sectionId]) ? $openedComments[$section->sectionId] : []);
        $main .= $section->getSectionType()->showMotionView($controller, $commentForm, $commOp);

        $main .= '</section>';
    }
}


if ($right == '') {
    echo $main;
} else {
    echo '<div class="row" style="margin-top: 2px;"><div class="col-md-9 motionMainCol">';
    echo $main;
    echo '</div><div class="col-md-3 motionRightCol">';
    echo $right;
    echo '</div></div>';
}

$currUserId = (\Yii::$app->user->isGuest ? 0 : \Yii::$app->user->id);
$supporters = $motion->getSupporters();

if (count($supporters) > 0) {
    echo '<section class="supporters"><h2 class="green">' . \Yii::t('motion', 'supporters_heading') . '</h2>
    <div class="content">';

    if (count($supporters) > 0) {
        echo '<ul>';
        foreach ($supporters as $supp) {
            echo '<li>';
            if ($supp->id == $currUserId) {
                echo '<span class="label label-info">' . \Yii::t('motion', 'supporting_you') . '</span> ';
            }
            echo Html::encode($supp->getNameWithOrga());
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<em>' . \Yii::t('motion', 'supporting_none') . '</em><br>';
    }
    echo "<br>";
    echo '</div></section>';
}

LayoutHelper::printSupportSection($motion, $motion->motionType->getSupportPolicy(), $supportStatus);

$amendments = $motion->getVisibleAmendments();
if (count($amendments) > 0 || $motion->motionType->getAmendmentPolicy()->getPolicyID() != IPolicy::POLICY_NOBODY) {
    echo '<section class="amendments"><h2 class="green">' . Yii::t('amend', 'amendments') . '</h2>
    <div class="content">';

    $policy = $motion->motionType->getAmendmentPolicy();
    if ($policy->checkCurrUserAmendment(true, true)) {
        echo '<div class="pull-right">';
        $title = '<span class="icon glyphicon glyphicon-flash"></span>';
        $title .= \Yii::t('motion', 'amendment_create');
        $amendCreateUrl = UrlHelper::createUrl(['amendment/create', 'motionId' => $motion->id]);
        echo '<a class="btn btn-default btn-sm" href="' . Html::encode($amendCreateUrl) . '">' . $title . '</a>';
        echo '</div>';
    }

    if (count($amendments) > 0) {
        echo '<ul class="amendments">';
        foreach ($amendments as $amend) {
            echo '<li>';
            $aename = $amend->titlePrefix;
            if ($aename == '') {
                $aename = $amend->id;
            }
            $amendLink  = UrlHelper::createAmendmentUrl($amend);
            $amendStati = Amendment::getStati();
            echo Html::a($aename, $amendLink, ['class' => 'amendment' . $amend->id]);
            echo ' (' . Html::encode($amend->getInitiatorsStr() . ', ' . $amendStati[$amend->status]) . ')';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<em>' . \Yii::t('motion', 'amends_none') . '</em>';
    }

    echo '</div></section>';
}


if ($commentWholeMotions && $motion->motionType->getCommentPolicy()->getPolicyID() != Nobody::getPolicyID()) {
    echo '<section class="comments"><h2 class="green">' . \Yii::t('motion', 'comments') . '</h2>';
    $form           = $commentForm;
    $screeningAdmin = User::currentUserHasPrivilege($motion->getConsultation(), User::PRIVILEGE_SCREENING);

    $screening = \Yii::$app->session->getFlash('screening', null, true);
    if ($screening) {
        echo '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">Success:</span>
                ' . Html::encode($screening) . '
            </div>';
    }

    if ($form === null || $form->paragraphNo != -1 || $form->sectionId !== null) {
        $form              = new \app\models\forms\CommentForm();
        $form->paragraphNo = -1;
        $form->sectionId   = -1;
    }

    $screeningQueue = 0;
    foreach ($motion->comments as $comment) {
        if ($comment->status == MotionComment::STATUS_SCREENING && $comment->paragraph == -1) {
            $screeningQueue++;
        }
    }
    if ($screeningQueue > 0) {
        echo '<div class="commentScreeningQueue">';
        if ($screeningQueue == 1) {
            echo \Yii::t('motion', 'comment_screen_queue_1');
        } else {
            echo str_replace('%NUM%', $screeningQueue, \Yii::t('motion', 'comment_screen_queue_x'));
        }
        echo '</div>';
    }

    $baseLink = UrlHelper::createMotionUrl($motion);
    foreach ($motion->getVisibleComments($screeningAdmin) as $comment) {
        if ($comment->paragraph == -1) {
            $commLink = UrlHelper::createMotionCommentUrl($comment);
            LayoutHelper::showComment($comment, $screeningAdmin, $baseLink, $commLink);
        }
    }

    if ($motion->motionType->getCommentPolicy()->checkCurrUser()) {
        LayoutHelper::showCommentForm($form, $motion->getConsultation(), -1, -1);
    } elseif ($motion->motionType->getCommentPolicy()->checkCurrUser(true, true)) {
        echo '<div class="alert alert-info" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in"></span>' .
            \Yii::t('motion', 'comment_login_hint') .
            '</div>';
    }
    echo '</section>';
}

$layout->addOnLoadJS('$.Antragsgruen.motionShow();');
