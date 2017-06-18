<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\MotionSupporter;
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
$layout->addAMDModule('frontend/MotionShow');

if ($controller->isRequestSet('backUrl') && $controller->isRequestSet('backTitle')) {
    $layout->addBreadcrumb($controller->getRequestValue('backTitle'), $controller->getRequestValue('backUrl'));
}
if (!$motion->getMyConsultation()->getForcedMotion()) {
    $layout->addBreadcrumb($motion->getBreadcrumbTitle());
}

$this->title = $motion->getTitleWithPrefix() . ' (' . $motion->getMyConsultation()->title . ', Antragsgrün)';

$sidebarRows = include(__DIR__ . DIRECTORY_SEPARATOR . '_view_sidebar.php');

$minimalisticUi          = $motion->getMyConsultation()->getSettings()->minimalisticUI;
$minHeight               = $sidebarRows * 40 - 100;
$supportCollectingStatus = ($motion->status == Motion::STATUS_COLLECTING_SUPPORTERS && !$motion->isDeadlineOver());

echo '<h1>' . $motion->getEncodedTitleWithPrefix() . '</h1>';

echo $layout->getMiniMenu('motionSidebarSmall');

echo '<div class="motionData" style="min-height: ' . $minHeight . 'px;">';

if (!$minimalisticUi) {
    include(__DIR__ . DIRECTORY_SEPARATOR . '_view_motiondata.php');
}

echo $controller->showErrors();


if ($supportCollectingStatus) {
    echo '<div class="content" style="margin-top: 0;">';
    echo '<div class="alert alert-info supportCollectionHint" role="alert" style="margin-top: 0;">';
    $min  = $motion->motionType->getMotionSupportTypeClass()->getMinNumberOfSupporters();
    $curr = count($motion->getSupporters());
    if ($curr >= $min) {
        echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], \Yii::t('motion', 'support_collection_reached_hint'));
    } else {
        echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], \Yii::t('motion', 'support_collection_hint'));

        if ($motion->motionType->policySupportMotions != IPolicy::POLICY_ALL && !User::getCurrentUser()) {
            $loginUrl = UrlHelper::createUrl(['user/login', 'backUrl' => \yii::$app->request->url]);
            echo '<div style="vertical-align: middle; line-height: 40px; margin-top: 20px;">';
            echo '<a href="' . Html::encode($loginUrl) . '" class="btn btn-default pull-right" rel="nofollow">' .
                '<span class="icon glyphicon glyphicon-log-in" aria-hidden="true"></span> ' .
                \Yii::t('base', 'menu_login') . '</a>';

            echo Html::encode(\Yii::t('structure', 'policy_logged_supp_denied'));
            echo '</div>';
        }
    }
    echo '</div></div>';
}
if ($motion->canFinishSupportCollection()) {
    echo Html::beginForm('', 'post', ['class' => 'motionSupportFinishForm']);

    echo '<div style="text-align: center; margin-bottom: 20px;">';

    echo '<button type="submit" name="motionSupportFinish" class="btn btn-success">';
    echo \Yii::t('motion', 'support_finish_btn');
    echo '</button>';

    echo Html::endForm();
}


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
        if ($motion->getMyConsultation()->getSettings()->lineLength > 80) {
            $main .= ' smallFont';
        }
        $main .= ' motionTextHolder' . $i . '" id="section_' . $section->sectionId . '">';
        if ($section->getSettings()->type != \app\models\sectionTypes\PDF::TYPE_PDF &&
            $section->getSettings()->type != \app\models\sectionTypes\PDF::TYPE_IMAGE
        ) {
            $main .= '<h3 class="green">' . Html::encode($section->getSettings()->title) . '</h3>';
        }

        $commOp = (isset($openedComments[$section->sectionId]) ? $openedComments[$section->sectionId] : []);
        $main   .= $section->getSectionType()->showMotionView($controller, $commentForm, $commOp);

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

$currUserId    = (\Yii::$app->user->isGuest ? 0 : \Yii::$app->user->id);
$supporters    = $motion->getSupporters();
$supportType   = $motion->motionType->getMotionSupportTypeClass();
$supportPolicy = $motion->motionType->getMotionSupportPolicy();

if (count($supporters) > 0 || $supportCollectingStatus || $supportPolicy->checkCurrUser()) {
    echo '<section class="supporters"><h2 class="green">' . \Yii::t('motion', 'supporters_heading') . '</h2>
    <div class="content">';

    $iAmSupporting        = false;
    $anonymouslySupported = \app\models\db\MotionSupporter::getMyAnonymousSupportIds();
    if (count($supporters) > 0) {
        echo '<ul>';
        foreach ($supporters as $supp) {
            /** @var MotionSupporter $supp */
            echo '<li>';
            if (($currUserId && $supp->userId == $currUserId) || in_array($supp->id, $anonymouslySupported)) {
                echo '<span class="label label-info">' . \Yii::t('motion', 'supporting_you') . '</span> ';
                $iAmSupporting = true;
            }
            echo Html::encode($supp->getNameWithOrga());
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<em>' . \Yii::t('motion', 'supporting_none') . '</em><br>';
    }
    echo '<br>';
    LayoutHelper::printSupportingSection($motion, $supportPolicy, $supportType, $iAmSupporting);
    echo '</div></section>';
}

LayoutHelper::printLikeDislikeSection($motion, $supportPolicy, $supportStatus);

$amendments = $motion->getVisibleAmendments();
if (count($amendments) > 0 || $motion->motionType->getAmendmentPolicy()->getPolicyID() != IPolicy::POLICY_NOBODY) {
    echo '<section class="amendments"><h2 class="green">' . Yii::t('amend', 'amendments') . '</h2>
    <div class="content">';

    if ($motion->isCurrentlyAmendable(false, true)) {
        echo '<div class="pull-right">';
        $title          = '<span class="icon glyphicon glyphicon-flash"></span>';
        $title          .= \Yii::t('motion', 'amendment_create');
        $amendCreateUrl = UrlHelper::createUrl(['amendment/create', 'motionSlug' => $motion->getMotionSlug()]);
        echo '<a class="btn btn-default btn-sm" href="' . Html::encode($amendCreateUrl) . '" rel="nofollow">' .
            $title . '</a>';
        echo '</div>';
    }

    // Global alternatives first, then sorted by titlePrefix
    usort($amendments, function (Amendment $amend1, Amendment $amend2) {
        if ($amend1->globalAlternative && !$amend2->globalAlternative) {
            return -1;
        }
        if (!$amend1->globalAlternative && $amend2->globalAlternative) {
            return 1;
        }
        return strnatcasecmp($amend1->titlePrefix, $amend2->titlePrefix);
    });

    if (count($amendments) > 0) {
        echo '<ul class="amendments">';
        foreach ($amendments as $amend) {
            echo '<li>';
            if ($amend->globalAlternative) {
                echo '<strong>' . \Yii::t('amend', 'global_alternative') . ':</strong> ';
            }
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
    $screeningAdmin = User::currentUserHasPrivilege($motion->getMyConsultation(), User::PRIVILEGE_SCREENING);

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
        $user              = User::getCurrentUser();
        if ($user) {
            $form->name  = $user->name;
            $form->email = $user->email;
        }
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
        LayoutHelper::showCommentForm($form, $motion->getMyConsultation(), -1, -1);
    } elseif ($motion->motionType->getCommentPolicy()->checkCurrUser(true, true)) {
        echo '<div class="alert alert-info" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in"></span>&nbsp; ' .
            \Yii::t('motion', 'comment_login_hint') .
            '</div>';
    }
    echo '</section>';
}
