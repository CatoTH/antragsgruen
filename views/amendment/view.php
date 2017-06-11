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
$controller = $this->context;
$layout     = $controller->layoutParams;
$layout->addAMDModule('frontend/AmendmentShow');
$consultation = $amendment->getMyConsultation();
$motion       = $amendment->getMyMotion();

if ($controller->isRequestSet('backUrl') && $controller->isRequestSet('backTitle')) {
    $layout->addBreadcrumb($controller->getRequestValue('backTitle'), $controller->getRequestValue('backUrl'));
    $layout->addBreadcrumb($amendment->getShortTitle());
} else {
    $motionUrl = UrlHelper::createMotionUrl($motion);
    $layout->addBreadcrumb($motion->getBreadcrumbTitle(), $motionUrl);
    if (!$consultation->getSettings()->hideTitlePrefix && $amendment->titlePrefix != '') {
        $layout->addBreadcrumb($amendment->titlePrefix);
    } else {
        $layout->addBreadcrumb(\Yii::t('amend', 'amendment'));
    }
}

$this->title = $amendment->getTitle() . ' (' . $consultation->title . ', Antragsgrün)';

$sidebarRows = include(__DIR__ . DIRECTORY_SEPARATOR . '_view_sidebar.php');

echo '<h1>' . Html::encode($amendment->getTitle()) . '</h1>';

$minHeight               = $sidebarRows * 40 - 100;
$supportCollectingStatus = (
    $amendment->status == Amendment::STATUS_COLLECTING_SUPPORTERS &&
    !$amendment->isDeadlineOver()
);

echo '<div class="motionData" style="min-height: ' . $minHeight . 'px;"><div class="content">';

echo '<table class="motionDataTable">
                <tr>
                    <th>' . Yii::t('amend', 'motion') . ':</th>
                    <td>' .
    Html::a($motion->title, UrlHelper::createMotionUrl($motion)) . '</td>
                </tr>
                <tr>
                    <th>' . Yii::t('amend', 'initiator'), ':</th>
                    <td>';

echo MotionLayoutHelper::formatInitiators($amendment->getInitiators(), $consultation);

echo '</td></tr>
                <tr class="statusRow"><th>' . \Yii::t('amend', 'status') . ':</th><td>';

$screeningMotionsShown = $consultation->getSettings()->screeningMotionsShown;
$statiNames            = Amendment::getStati();
switch ($amendment->status) {
    case Amendment::STATUS_SUBMITTED_UNSCREENED:
    case Amendment::STATUS_SUBMITTED_UNSCREENED_CHECKED:
        echo '<span class="unscreened">' . Html::encode($statiNames[$amendment->status]) . '</span>';
        break;
    case Amendment::STATUS_SUBMITTED_SCREENED:
        echo '<span class="screened">' . \Yii::t('amend', 'screened_hint') . '</span>';
        break;
    case Amendment::STATUS_COLLECTING_SUPPORTERS:
        echo Html::encode($statiNames[$amendment->status]);
        echo ' <small>(' . \Yii::t('motion', 'supporting_permitted') . ': ';
        echo IPolicy::getPolicyNames()[$motion->motionType->policySupportAmendments] . ')</small>';
        break;
    default:
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
echo '<tr><th>' . \Yii::t('amend', ($amendment->isSubmitted() ? 'submitted_on' : 'created_on')) . ':</th>
       <td>' . Tools::formatMysqlDateTime($amendment->dateCreation) . '</td>
                </tr>';
echo '</table>';

echo $controller->showErrors();


if ($supportCollectingStatus) {
    echo '<div class="alert alert-info supportCollectionHint" role="alert">';
    $min  = $motion->motionType->getAmendmentSupportTypeClass()->getMinNumberOfSupporters();
    $curr = count($amendment->getSupporters());
    if ($curr >= $min) {
        echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], \Yii::t('amend', 'support_collection_reached_hint'));
    } else {
        echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], \Yii::t('amend', 'support_collection_hint'));
    }
    if ($motion->motionType->policySupportAmendments != IPolicy::POLICY_ALL && !User::getCurrentUser()) {
        $loginUrl = UrlHelper::createUrl(['user/login', 'backUrl' => \yii::$app->request->url]);
        echo '<div style="vertical-align: middle; line-height: 40px; margin-top: 20px;">';
        echo '<a href="' . Html::encode($loginUrl) . '" class="btn btn-default pull-right" rel="nofollow">' .
            '<span class="icon glyphicon glyphicon-log-in" aria-hidden="true"></span> ' .
            \Yii::t('base', 'menu_login') . '</a>';

        echo Html::encode(\Yii::t('structure', 'policy_logged_supp_denied'));
        echo '</div>';
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

$currUserId    = (\Yii::$app->user->isGuest ? 0 : \Yii::$app->user->id);
$supporters    = $amendment->getSupporters();
$supportPolicy = $motion->motionType->getAmendmentSupportPolicy();
$supportType   = $motion->motionType->getAmendmentSupportTypeClass();

if (count($supporters) > 0 || $supportCollectingStatus || $supportPolicy->checkCurrUser()) {
    echo '<section class="supporters"><h2 class="green">' . \Yii::t('motion', 'supporters_heading') . '</h2>
    <div class="content">';

    $iAmSupporting        = false;
    $anonymouslySupported = \app\models\db\AmendmentSupporter::getMyAnonymousSupportIds();
    if (count($supporters) > 0) {
        echo '<ul>';
        foreach ($supporters as $supp) {
            echo '<li>';
            if (($currUserId && $supp->userId == $currUserId) || in_array($supp->id, $anonymouslySupported)) {
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

if ($motion->motionType->policyComments != IPolicy::POLICY_NOBODY) {
    echo '<section class="comments"><h2 class="green">' . \Yii::t('amend', 'comments_title') . '</h2>';

    $form        = $commentForm;
    $screenAdmin = User::currentUserHasPrivilege($consultation, User::PRIVILEGE_SCREENING);

    if ($form === null || $form->paragraphNo != -1 || $form->sectionId != -1) {
        $form              = new \app\models\forms\CommentForm();
        $form->paragraphNo = -1;
        $form->sectionId   = -1;
        $user              = User::getCurrentUser();
        if ($user) {
            $form->name  = $user->name;
            $form->email = $user->email;
        }
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

    if ($motion->motionType->getCommentPolicy()->checkCurrUser()) {
        MotionLayoutHelper::showCommentForm($form, $consultation, -1, -1);
    } elseif ($motion->motionType->getCommentPolicy()->checkCurrUser(true, true)) {
        echo '<div class="alert alert-info" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in"></span>&nbsp; ' .
            \Yii::t('amend', 'comments_please_log_in') . '</div>';
    }
    echo '</section>';
}
