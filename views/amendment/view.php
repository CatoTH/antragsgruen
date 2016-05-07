<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\AmendmentSection;
use app\models\db\User;
use app\models\forms\CommentForm;
use app\models\policies\IPolicy;
use app\views\motion\LayoutHelper as MotionLayoutHelper;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var bool $editLink
 * @var int[] $openedComments
 * @var string|null $adminEdit
 * @var null|string $supportStatus
 * @var null|CommentForm $commentForm
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$layout       = $controller->layoutParams;
$consultation = $amendment->getMyConsultation();

if ($controller->isRequestSet('backUrl') && $controller->isRequestSet('backTitle')) {
    $layout->addBreadcrumb($controller->getRequestValue('backTitle'), $controller->getRequestValue('backUrl'));
    $layout->addBreadcrumb($amendment->getShortTitle());
} else {
    $motionUrl = UrlHelper::createMotionUrl($amendment->getMyMotion());
    $layout->addBreadcrumb($amendment->getMyMotion()->motionType->titleSingular, $motionUrl);
    if (!$consultation->getSettings()->hideTitlePrefix && $amendment->titlePrefix != '') {
        $layout->addBreadcrumb($amendment->titlePrefix);
    } else {
        $layout->addBreadcrumb(\Yii::t('amend', 'amendment'));
    }
}

$this->title = $amendment->getTitle() . ' (' . $consultation->title . ', Antragsgrün)';


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


echo '<h1>' . Html::encode($amendment->getTitle()) . '</h1>';

$minHeight = $sidebarRows * 40 - 100;

echo '<div class="motionData" style="min-height: ' . $minHeight . 'px;"><div class="content">';

echo '<table class="motionDataTable">
                <tr>
                    <th>' . Yii::t('amend', 'motion') . ':</th>
                    <td>' .
    Html::a($amendment->getMyMotion()->title, UrlHelper::createMotionUrl($amendment->getMyMotion())) . '</td>
                </tr>
                <tr>
                    <th>' . Yii::t('amend', 'initiator'), ':</th>
                    <td>';

echo MotionLayoutHelper::formatInitiators($amendment->getInitiators(), $consultation);

echo '</td></tr>
                <tr class="statusRow"><th>' . \Yii::t('amend', 'status') . ':</th><td>';

$screeningMotionsShown = $consultation->getSettings()->screeningMotionsShown;
$statiNames            = Amendment::getStati();
if ($amendment->status == Amendment::STATUS_SUBMITTED_UNSCREENED) {
    echo '<span class="unscreened">' . Html::encode($statiNames[$amendment->status]) . '</span>';
} elseif ($amendment->status == Amendment::STATUS_SUBMITTED_SCREENED && $screeningMotionsShown) {
    echo '<span class="screened">' . \Yii::t('amend', 'screened_hint') . '</span>';
} else {
    echo Html::encode($statiNames[$amendment->status]);
}
if (trim($amendment->statusString) != '') {
    echo " <small>(" . Html::encode($amendment->statusString) . ")</string>";
}
echo '</td>
                </tr>';

if ($amendment->dateResolution != '') {
    echo '<tr><th>' . \Yii::t('amend', 'resoluted_on') . ':</th>
       <td>' . Tools::formatMysqlDate($amendment->dateResolution) . '</td>
     </tr>';
}
echo '<tr><th>' . \Yii::t('amend', 'submitted_on') . ':</th>
       <td>' . Tools::formatMysqlDateTime($amendment->dateCreation) . '</td>
                </tr>';
echo '</table>';

echo $controller->showErrors();


if ($amendment->status == Amendment::STATUS_COLLECTING_SUPPORTERS) {
    echo '<div class="alert alert-info supportCollectionHint" role="alert">';
    $min  = $amendment->getMyMotion()->motionType->getAmendmentSupportTypeClass()->getMinNumberOfSupporters();
    $curr = count($amendment->getSupporters());
    if ($curr >= $min) {
        echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], \Yii::t('amend', 'support_collection_reached_hint'));
    } else {
        echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], \Yii::t('amend', 'support_collection_hint'));
    }
    echo '</div>';
}
if ($amendment->canFinishSupportCollection()) {
    echo Html::beginForm('', 'post', ['class' => 'amendmentSupportFinishForm']);

    echo '<div style="text-align: center; margin-bottom: 20px;">';

    echo '<button type="submit" name="amendmentSupportFinish" class="btn btn-success">';
    echo \Yii::t('amend', 'support_finish_btn');
    echo '</button>';

    echo Html::endForm();
}

echo '</div>';
echo '</div>';

if ($amendment->changeEditorial != '') {
    echo '<section id="section_editorial" class="motionTextHolder">';
    echo '<h3 class="green">' . \Yii::t('amend', 'editorial_hint') . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeEditorial;
    echo '</div></div></section>';
}

/** @var AmendmentSection[] $sections */
$sections = $amendment->getSortedSections(false);
foreach ($sections as $section) {
    echo $section->getSectionType()->getAmendmentFormatted();
}


if ($amendment->changeExplanation != '') {
    echo '<section id="amendmentExplanation" class="motionTextHolder">';
    echo '<h3 class="green">' . \Yii::t('amend', 'reason') . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeExplanation;
    echo '</div></div>';
    echo '</section>';
}

$currUserId = (\Yii::$app->user->isGuest ? 0 : \Yii::$app->user->id);
$supporters = $amendment->getSupporters();
$supportPolicy = $amendment->getMyMotion()->motionType->getAmendmentSupportPolicy();
$supportType = $amendment->getMyMotion()->motionType->getAmendmentSupportTypeClass();

if (count($supporters) > 0 || $amendment->status == Amendment::STATUS_COLLECTING_SUPPORTERS) {
    echo '<section class="supporters"><h2 class="green">' . \Yii::t('motion', 'supporters_heading') . '</h2>
    <div class="content">';

    $iAmSupporting = false;
    if (count($supporters) > 0) {
        echo '<ul>';
        foreach ($supporters as $supp) {
            echo '<li>';
            if ($currUserId && $supp->userId == $currUserId) {
                echo '<span class="label label-info">' . \Yii::t('amend', 'supporter_you') . '</span> ';
                $iAmSupporting = true;
            }
            echo Html::encode($supp->getNameWithOrga());
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<em>' . \Yii::t('amend', 'supporter_none') . '</em><br>';
    }
    echo '<br>';
    MotionLayoutHelper::printSupportingSection($amendment, $supportPolicy, $supportType, $iAmSupporting);
    echo '</div></section>';
}

MotionLayoutHelper::printLikeDislikeSection($amendment, $supportPolicy, $supportStatus);

if ($amendment->getMyMotion()->motionType->policyComments != IPolicy::POLICY_NOBODY) {
    echo '<section class="comments"><h2 class="green">' . \Yii::t('amend', 'comments_title') . '</h2>';

    $form        = $commentForm;
    $screenAdmin = User::currentUserHasPrivilege($consultation, User::PRIVILEGE_SCREENING);

    if ($form === null || $form->paragraphNo != -1 || $form->sectionId != -1) {
        $form              = new \app\models\forms\CommentForm();
        $form->paragraphNo = -1;
        $form->sectionId   = -1;
    }

    $baseLink     = UrlHelper::createAmendmentUrl($amendment);
    $visibleStati = [AmendmentComment::STATUS_VISIBLE];
    if ($screenAdmin) {
        $visibleStati[] = AmendmentComment::STATUS_SCREENING;
    }
    $screeningQueue = 0;
    foreach ($amendment->comments as $comment) {
        if ($comment->status == AmendmentComment::STATUS_SCREENING) {
            $screeningQueue++;
        }
    }
    if ($screeningQueue > 0) {
        echo '<div class="commentScreeningQueue">';
        if ($screeningQueue == 1) {
            echo \Yii::t('amend', 'comments_screening_queue_1');
        } else {
            echo str_replace('%NUM%', $screeningQueue, \Yii::t('amend', 'comments_screening_queue_x'));
        }
        echo '</div>';
    }
    foreach ($amendment->comments as $comment) {
        if ($comment->paragraph == -1 && in_array($comment->status, $visibleStati)) {
            $commLink = UrlHelper::createAmendmentCommentUrl($comment);
            MotionLayoutHelper::showComment($comment, $screenAdmin, $baseLink, $commLink);
        }
    }

    if ($amendment->getMyMotion()->motionType->getCommentPolicy()->checkCurrUser()) {
        MotionLayoutHelper::showCommentForm($form, $consultation, -1, -1);
    } elseif ($amendment->getMyMotion()->motionType->getCommentPolicy()->checkCurrUser(true, true)) {
        echo '<div class="alert alert-info" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in"></span>&nbsp; ' .
            \Yii::t('amend', 'comments_please_log_in') . '</div>';
    }
    echo '</section>';
}

$layout->addOnLoadJS('jQuery.Antragsgruen.amendmentShow();');
