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

$consultation = $amendment->getMyConsultation();
$motion = $amendment->getMyMotion();
$motionType   = $motion->getMyMotionType();
$hasPp = $amendment->getMyMotionType()->getSettingsObj()->hasProposedProcedure;
$hasPpAdminbox = User::havePrivilege($consultation, User::PRIVILEGE_CHANGE_PROPOSALS);

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$layout->addAMDModule('frontend/AmendmentShow');
$layout->loadVue();
$layout->addVueTemplate('@app/views/shared/fullscreen-projector.vue.php');
if ($hasPp && $hasPpAdminbox) {
    $layout->loadSelectize();
}

if ($controller->isRequestSet('backUrl') && $controller->isRequestSet('backTitle')) {
    $layout->addBreadcrumb($controller->getRequestValue('backTitle'), $controller->getRequestValue('backUrl'));
    $layout->addBreadcrumb($amendment->getShortTitle());
} else {
    if (!$motionType->amendmentsOnly) {
        $motionUrl = UrlHelper::createMotionUrl($motion);
        $layout->addBreadcrumb($motion->getBreadcrumbTitle(), $motionUrl);
    }
    if (!$consultation->getSettings()->hideTitlePrefix && $amendment->titlePrefix != '') {
        $layout->addBreadcrumb($amendment->titlePrefix);
    } else {
        $layout->addBreadcrumb(Yii::t('amend', 'amendment'));
    }
}

$this->title = $amendment->getTitle() . ' (' . $consultation->title . ')';

$sidebarRows = include(__DIR__ . DIRECTORY_SEPARATOR . '_view_sidebar.php');

if (User::getCurrentUser()) {
    $fullscreenInitData = json_encode([
        'consultation_url' => UrlHelper::createUrl(['/consultation/rest']),
        'init_imotion_url' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment, 'rest')),
    ]);
    $fullscreenButton = '<button type="button" title="' . Yii::t('motion', 'fullscreen') . '" class="btn btn-link btnFullscreen"
        data-antragsgruen-widget="frontend/FullscreenToggle" data-vue-element="fullscreen-projector" data-vue-initdata="' . Html::encode($fullscreenInitData) . '">
        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
        <span class="sr-only">' . Yii::t('motion', 'fullscreen') . '</span>
    </button>';
} else {
    $fullscreenButton = '';
}

echo '<h1>' . Html::encode($amendment->getTitle()) . $fullscreenButton . '</h1>';

if ($consultation->getSettings()->hasSpeechLists) {
    echo $this->render('@app/views/speech/_footer_widget', ['queue' => $motion->getActiveSpeechQueue()]);
}

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
    $curr          = count($amendment->getSupporters(true));
    if ($amendment->hasEnoughSupporters($supportType)) {
        $textTmpl = $motion->getMyMotionType()->getConsultationTextWithFallback('amend', 'support_collection_reached_hint');
        echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], $textTmpl);
    } else {
        $minAll        = $min + 1;
        $currAll       = $curr + count($motion->getInitiators());
        $minFemale = $supportType->getSettingsObj()->minSupportersFemale;
        if ($minFemale) {
            $currFemale = $amendment->getSupporterCountByGender('female');
            echo str_replace(
                ['%MIN%', '%CURR%', '%MIN_ALL%', '%CURR_ALL%', '%MIN_F%', '%CURR_F%'],
                [$min, $curr, $minAll, $currAll, $minFemale, $currFemale],
                Yii::t('motion', 'support_collection_hint_female')
            );
        } else {
            $textTmpl = $motion->getMyMotionType()->getConsultationTextWithFallback('amend', 'support_collection_hint');
            echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], $textTmpl);
        }
    }
    if ($motion->getMyMotionType()->policySupportAmendments !== IPolicy::POLICY_ALL && !User::getCurrentUser()) {
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

if ($hasPp) {
    if ($hasPpAdminbox) {
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
$supporters    = $amendment->getSupporters(true);
$supportPolicy = $motion->getMyMotionType()->getAmendmentSupportPolicy();
$supportType   = $motion->getMyMotionType()->getAmendmentSupportTypeClass();

$loginlessSupported = \app\models\db\AmendmentSupporter::getMyLoginlessSupportIds();
echo MotionLayoutHelper::printSupportingSection($amendment, $supporters, $supportPolicy, $supportType, $loginlessSupported);
echo MotionLayoutHelper::printLikeDislikeSection($amendment, $supportPolicy, $supportStatus);

$alternativeCommentView = \app\models\layoutHooks\Layout::getAmendmentAlternativeComments($amendment);
if ($alternativeCommentView) {
    echo $alternativeCommentView;
} elseif ($motion->getMyMotionType()->maySeeIComments()) {
    echo $this->render('_view_comments', ['amendment' => $amendment, 'commentForm' => $commentForm]);
}
