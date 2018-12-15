<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\User;
use app\models\forms\CommentForm;
use app\models\policies\IPolicy;
use app\views\motion\LayoutHelper as MotionLayoutHelper;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var int[] $openedComments
 * @var string|null $adminEdit
 * @var null|string $supportStatus
 * @var null|CommentForm $commentForm
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$layout->addAMDModule('frontend/AmendmentShow');
$layout->loadFuelux();
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

$this->title = $amendment->getTitle() . ' (' . $consultation->title . ')';

$sidebarRows = include(__DIR__ . DIRECTORY_SEPARATOR . '_view_sidebar.php');

echo '<h1>' . Html::encode($amendment->getTitle()) . '</h1>';

$minHeight               = ($sidebarRows * 40 - 100) . 'px';
$supportCollectingStatus = (
    $amendment->status == Amendment::STATUS_COLLECTING_SUPPORTERS &&
    !$amendment->isDeadlineOver()
);

echo '<div class="motionData" style="min-height: ' . $minHeight . ';"><div class="content">';

echo $this->render('_view_amendmentdata', [
    'amendment' => $amendment,
]);

echo $controller->showErrors();


if ($supportCollectingStatus) {
    echo '<div class="alert alert-info supportCollectionHint" role="alert">';
    $min  = $motion->motionType->getAmendmentSupportTypeClass()->getSettingsObj()->minSupporters;
    $curr = count($amendment->getSupporters());
    if ($curr >= $min) {
        echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], \Yii::t('amend', 'support_collection_reached_hint'));
    } else {
        echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], \Yii::t('amend', 'support_collection_hint'));
    }
    if ($motion->motionType->policySupportAmendments !== IPolicy::POLICY_ALL && !User::getCurrentUser()) {
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

    echo '<button type="submit" name="amendmentSupportFinish" class="btn btn-success">';
    echo \Yii::t('amend', 'support_finish_btn');
    echo '</button>';

    echo Html::endForm();
}

echo '</div>';
echo '</div>';

if (User::getCurrentUser() && !$amendment->getPrivateComment(null, -1)) {
    ?>
    <div class="privateNoteOpener">
        <button class="btn btn-link btn-sm">
            <span class="glyphicon glyphicon-pushpin"></span>
            <?= \Yii::t('motion', 'private_notes') ?>
        </button>
    </div>
    <?php
}

if ($amendment->getMyMotionType()->getSettingsObj()->hasProposedProcedure) {
    if (User::havePrivilege($consultation, User::PRIVILEGE_CHANGE_PROPOSALS)) {
        ?>
        <div class="proposedChangesOpener">
            <button class="btn btn-default btn-sm">
                <span class="glyphicon glyphicon-chevron-down"></span>
                <?= \Yii::t('amend', 'proposal_open') ?>
            </button>
        </div>
        <?php

        echo $this->render('_set_proposed_procedure', [
            'amendment' => $amendment,
            'context'   => 'view',
            'msgAlert'  => null,
        ]);
    }
    if ($amendment->proposalFeedbackHasBeenRequested() && $amendment->iAmInitiator()) {
        echo $this->render('_view_agree_to_proposal', ['amendment' => $amendment]);
    }
}

echo $this->render('_view_text', ['amendment' => $amendment]);

$currUserId    = (\Yii::$app->user->isGuest ? 0 : \Yii::$app->user->id);
$supporters    = $amendment->getSupporters();
$supportPolicy = $motion->motionType->getAmendmentSupportPolicy();
$supportType   = $motion->motionType->getAmendmentSupportTypeClass();

if (count($supporters) > 0 || $supportCollectingStatus || $supportPolicy->checkCurrUser()) {
    echo '<section class="supporters" id="supporters">
    <h2 class="green">' . \Yii::t('motion', 'supporters_heading') . '</h2>
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

if ($motion->motionType->policyComments !== IPolicy::POLICY_NOBODY) {
    echo $this->render('_view_comments', ['amendment' => $amendment, 'commentForm' => $commentForm]);
}
