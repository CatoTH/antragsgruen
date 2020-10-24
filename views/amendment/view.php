<?php

use app\components\UrlHelper;
use app\models\db\{Amendment, User};
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
 * @var string|null $procedureToken
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
        $layout->addBreadcrumb(Yii::t('amend', 'amendment'));
    }
}

$this->title = $amendment->getTitle() . ' (' . $consultation->title . ')';

$sidebarRows = include(__DIR__ . DIRECTORY_SEPARATOR . '_view_sidebar.php');

echo '<h1>' . Html::encode($amendment->getTitle()) . '</h1>';

$minHeight               = max(0, $sidebarRows * 40 - 100) . 'px';
$supportCollectingStatus = (
    $amendment->status === Amendment::STATUS_COLLECTING_SUPPORTERS &&
    !$amendment->isDeadlineOver()
);

echo '<div class="motionData" style="min-height: ' . $minHeight . ';"><div class="content">';

echo $this->render('_view_amendmentdata', [
    'amendment' => $amendment,
]);

echo $controller->showErrors();


if ($supportCollectingStatus) {
    echo '<div class="alert alert-info supportCollectionHint" role="alert">';
    $supportType   = $amendment->getMyMotionType()->getAmendmentSupportTypeClass();
    $min           = $supportType->getSettingsObj()->minSupporters;
    $curr          = count($amendment->getSupporters());
    $missingFemale = $amendment->getMissingSupporterCountByGender($supportType, 'female');
    if ($curr >= $min && !$missingFemale) {
        echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], Yii::t('amend', 'support_collection_reached_hint'));
    } else {
        $minFemale = $supportType->getSettingsObj()->minSupportersFemale;
        if ($minFemale) {
            $currFemale = $amendment->getSupporterCountByGender('female');
            echo str_replace(
                ['%MIN%', '%CURR%', '%MIN_F%', '%CURR_F%'],
                [$min, $curr, $minFemale, $currFemale],
                Yii::t('motion', 'support_collection_hint_female')
            );
        } else {
            echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], Yii::t('amend', 'support_collection_hint'));
        }
    }
    if ($motion->motionType->policySupportAmendments !== IPolicy::POLICY_ALL && !User::getCurrentUser()) {
        $loginUrl = UrlHelper::createUrl(['user/login', 'backUrl' => Yii::$app->request->url]);
        echo '<div style="vertical-align: middle; line-height: 40px; margin-top: 20px;">';
        echo '<a href="' . Html::encode($loginUrl) . '" class="btn btn-default pull-right" rel="nofollow">' .
             '<span class="icon glyphicon glyphicon-log-in" aria-hidden="true"></span> ' .
             Yii::t('base', 'menu_login') . '</a>';

        echo Html::encode(Yii::t('structure', 'policy_logged_supp_denied'));
        echo '</div>';
    }
    echo '</div>';
}
if ($amendment->canFinishSupportCollection()) {
    echo Html::beginForm('', 'post', ['class' => 'amendmentSupportFinishForm']);

    echo '<button type="submit" name="amendmentSupportFinish" class="btn btn-success">';
    echo Yii::t('amend', 'support_finish_btn');
    echo '</button>';

    echo Html::endForm();
}

echo '</div>';
echo '</div>';

if (User::getCurrentUser() && !$amendment->getPrivateComment()) {
    ?>
    <div class="privateNoteOpener">
        <button class="btn btn-link btn-sm" tabindex="0" type="button">
            <span class="glyphicon glyphicon-pushpin" aria-hidden="true"></span>
            <?= Yii::t('motion', 'private_notes') ?>
        </button>
    </div>
    <?php
}

if ($amendment->getMyMotionType()->getSettingsObj()->hasProposedProcedure) {
    if (User::havePrivilege($consultation, User::PRIVILEGE_CHANGE_PROPOSALS)) {
        ?>
        <div class="proposedChangesOpener">
            <button class="btn btn-default btn-sm">
                <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
                <?= Yii::t('amend', 'proposal_open') ?>
            </button>
        </div>
        <?php

        echo $this->render('_set_proposed_procedure', [
            'amendment' => $amendment,
            'context'   => 'view',
            'msgAlert'  => null,
        ]);
    }
    if ($amendment->proposalFeedbackHasBeenRequested() && $amendment->canSeeProposedProcedure($procedureToken)) {
        echo $this->render('_view_agree_to_proposal', ['amendment' => $amendment, 'procedureToken' => $procedureToken]);
    }
}

if ($amendment->status === Amendment::STATUS_DRAFT) {
    ?>
    <div class="content">
        <div class="alert alert-info alertDraft">
            <p><?= Yii::t('motion', 'info_draft_admin') ?></p>
        </div>
    </div>
    <?php
}

echo $this->render('_view_text', ['amendment' => $amendment, 'procedureToken' => $procedureToken]);

$currUserId    = (Yii::$app->user->isGuest ? 0 : Yii::$app->user->id);
$supporters    = $amendment->getSupporters();
$supportPolicy = $motion->motionType->getAmendmentSupportPolicy();
$supportType   = $motion->motionType->getAmendmentSupportTypeClass();

if (count($supporters) > 0 || $supportCollectingStatus || $supportPolicy->checkCurrUser()) {
    echo '<section class="supporters" id="supporters">
    <h2 class="green">' . Yii::t('motion', 'supporters_heading') . '</h2>
    <div class="content">';

    $iAmSupporting        = false;
    $anonymouslySupported = \app\models\db\AmendmentSupporter::getMyAnonymousSupportIds();
    if (count($supporters) > 0) {
        echo '<ul>';
        foreach ($supporters as $supp) {
            echo '<li>';
            if (($currUserId && $supp->userId == $currUserId) || in_array($supp->id, $anonymouslySupported)) {
                echo '<span class="label label-info">' . Yii::t('amend', 'supporter_you') . '</span> ';
                $iAmSupporting = true;
            }
            echo Html::encode($supp->getNameWithOrga());
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<em>' . Yii::t('amend', 'supporter_none') . '</em><br>';
    }
    echo '<br>';
    MotionLayoutHelper::printSupportingSection($amendment, $supportPolicy, $supportType, $iAmSupporting);
    echo '</div></section>';
}

MotionLayoutHelper::printLikeDislikeSection($amendment, $supportPolicy, $supportStatus);

$alternativeCommentView = \app\models\layoutHooks\Layout::getAmendmentAlternativeComments($amendment);
if ($alternativeCommentView) {
    echo $alternativeCommentView;
} elseif ($motion->getMyMotionType()->policyComments !== IPolicy::POLICY_NOBODY) {
    echo $this->render('_view_comments', ['amendment' => $amendment, 'commentForm' => $commentForm]);
}
