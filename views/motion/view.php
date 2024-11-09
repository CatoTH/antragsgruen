<?php

use app\components\UrlHelper;
use app\models\sectionTypes\ISectionType;
use app\models\settings\{Consultation as ConsultationSettings, PrivilegeQueryContext, Privileges};
use app\models\db\{Motion, MotionComment, MotionSupporter, User};
use app\models\forms\CommentForm;
use app\models\policies\IPolicy;
use app\views\motion\LayoutHelper;
use yii\helpers\Html;

/**
 * @var bool $reducedNavigation
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var int[] $openedComments
 * @var string|null $adminEdit
 * @var null|string $supportStatus
 * @var null|CommentForm $commentForm
 * @var bool $commentWholeMotions
 * @var string|null $procedureToken
 */

$consultation = $motion->getMyConsultation();
$hasPp = $motion->getMyMotionType()->getSettingsObj()->hasProposedProcedure;
$hasPpAdminbox = ($hasPp && !$motion->isResolution() && $motion->canEditLimitedProposedProcedure());

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$layout->addAMDModule('frontend/MotionShow');
$layout->loadVue();
$layout->addFullscreenTemplates();
if ($hasPp && $hasPpAdminbox) {
    $layout->loadSelectize();
}

if (
    $consultation->getSettings()->startLayoutType === ConsultationSettings::START_LAYOUT_TAGS && $consultation->getSettings()->homepageByTag &&
    count($motion->tags) > 0
) {
    if ($motion->isResolution()) {
        if ($consultation->getSettings()->startLayoutResolutions === ConsultationSettings::START_LAYOUT_RESOLUTIONS_SEPARATE) {
            $layout->addBreadcrumb(Yii::t('con', 'resolutions'), UrlHelper::createUrl(['/consultation/resolutions']));
        }
        $layout->addBreadcrumb(Yii::t('admin', 'bread_tag'), UrlHelper::createUrl(['/consultation/tags-resolutions', 'tagId' => $motion->tags[0]->id]));
    } else {
        if ($consultation->getSettings()->startLayoutResolutions === ConsultationSettings::START_LAYOUT_RESOLUTIONS_DEFAULT) {
            $layout->addBreadcrumb(Yii::t('con', 'All Motions'), UrlHelper::createUrl(['/consultation/motions']));
        }
        $layout->addBreadcrumb(Yii::t('admin', 'bread_tag'), UrlHelper::createUrl(['/consultation/tags-motions', 'tagId' => $motion->tags[0]->id]));
    }
}
if ($controller->isRequestSet('backUrl') && $controller->isRequestSet('backTitle')) {
    $layout->addBreadcrumb($controller->getRequestValue('backTitle'), $controller->getRequestValue('backUrl'));
}
if (!$motion->getMyConsultation()->getForcedMotion()) {
    $layout->addBreadcrumb($motion->getBreadcrumbTitle());
}

if ($motion->isResolution()) {
    $this->title = $motion->getTitleWithIntro() . ' (' . $motion->getMyConsultation()->title . ')';
} else {
    $this->title = $motion->getTitleWithPrefix() . ' (' . $motion->getMyConsultation()->title . ')';
}

foreach ($motion->getActiveSections(ISectionType::TYPE_IMAGE) as $image) {
    if ($layout->ogImage === '') {
        /** @var \app\models\sectionTypes\Image $imageType */
        $imageType = $image->getSectionType();
        $layout->ogImage = $imageType->getImageUrl(true);
    }
}

$sidebarRows = include(__DIR__ . DIRECTORY_SEPARATOR . '_view_sidebar.php');

$minHeight               = max($sidebarRows * 40 - 100, 0);
$supportCollectingStatus = ($motion->status === Motion::STATUS_COLLECTING_SUPPORTERS && !$motion->isDeadlineOver());

if (User::getCurrentUser()) {
    $fullscreenInitData = json_encode([
        'consultation_url' => UrlHelper::createUrl(['/consultation/rest']),
        'init_page' => 'motion-' . $motion->id,
        'init_content_url' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion, 'rest')),
    ]);
    $fullscreenButton = '<button type="button" title="' . Yii::t('motion', 'fullscreen') . '" class="btn btn-link btnFullscreen"
        data-antragsgruen-widget="frontend/FullscreenToggle" data-vue-element="fullscreen-projector" data-vue-initdata="' . Html::encode($fullscreenInitData) . '">
        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
        <span class="sr-only">' . Yii::t('motion', 'fullscreen') . '</span>
    </button>';
} else {
    $fullscreenButton = '';
}

echo '<div class="primaryHeader">';
if ($motion->isResolution()) {
    echo '<h1>' . Html::encode($motion->getTitleWithIntro()) . '</h1>';
} else {
    echo '<h1>' . $motion->getEncodedTitleWithPrefix() . '</h1>';
}
echo $fullscreenButton;
echo '</div>';

if ($consultation->getSettings()->hasSpeechLists) {
    // Should be after h1 (because of CSS border-radius to .well :first-child),
    // but rather early in the context (because it should be easy reachable using keyboard / tabindex)
    echo $this->render('@app/views/speech/_footer_widget', ['queue' => $motion->getActiveSpeechQueue()]);
}

echo $layout->getMiniMenu('motionSidebarSmall');

echo $this->render('_view_prevnext', ['motion' => $motion, 'top' => true, 'reducedNavigation' => $reducedNavigation]);

echo '<div class="motionData" style="min-height: ' . $minHeight . 'px;">';

include(__DIR__ . DIRECTORY_SEPARATOR . '_view_motiondata.php');

echo $controller->showErrors(true);

if ($supportCollectingStatus) {
    echo '<div class="content" style="margin-top: 0;">';
    echo '<div class="alert alert-info supportCollectionHint" role="alert" style="margin-top: 0;">';
    $supportType   = $motion->getMyMotionType()->getMotionSupportTypeClass();
    $min           = $supportType->getSettingsObj()->minSupporters;
    $curr          = count($motion->getSupporters(true));
    if ($motion->hasEnoughSupporters($supportType)) {
        $textTmpl = $motion->getMyMotionType()->getConsultationTextWithFallback('motion', 'support_collection_reached_hint');
        if ($supportType->getSettingsObj()->allowMoreSupporters) {
            $textTmpl .= ' ' . $motion->getMyMotionType()->getConsultationTextWithFallback('motion', 'support_collection_reached_hint_m');
        }
        echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], $textTmpl);
    } else {
        $minAll        = $min + 1;
        $currAll       = $curr + count($motion->getInitiators());
        $minFemale = $supportType->getSettingsObj()->minSupportersFemale;
        if ($minFemale) {
            $currFemale = $motion->getSupporterCountByGender('female');
            echo str_replace(
                ['%MIN%', '%CURR%', '%MIN_ALL%', '%CURR_ALL%', '%MIN_F%', '%CURR_F%'],
                [$min, $curr, $minAll, $currAll, $minFemale, $currFemale],
                Yii::t('motion', 'support_collection_hint_female')
            );
        } else {
            $textTmpl = $motion->getMyMotionType()->getConsultationTextWithFallback('motion', 'support_collection_hint');
            echo str_replace(['%MIN%', '%CURR%'], [$min, $curr], $textTmpl);
        }

        if (!is_a($motion->getMyMotionType()->getMotionSupportPolicy(), \app\models\policies\All::class) && !User::getCurrentUser()) {
            $loginUrl = UrlHelper::createUrl(['user/login', 'backUrl' => Yii::$app->request->url]);
            echo '<div style="vertical-align: middle; line-height: 40px; margin-top: 20px;">';
            echo '<a href="' . Html::encode($loginUrl) . '" class="btn btn-default pull-right" rel="nofollow">' .
                 '<span class="icon glyphicon glyphicon-log-in" aria-hidden="true"></span> ' .
                 Yii::t('base', 'menu_login') . '</a>';

            echo Html::encode(Yii::t('structure', 'policy_logged_supp_denied'));
            echo '</div>';
        }
    }
    echo '</div></div>';
}
if ($motion->canFinishSupportCollection()) {
    echo Html::beginForm('', 'post', ['class' => 'motionSupportFinishForm']);

    echo '<button type="submit" name="motionSupportFinish" class="btn btn-success">';
    echo Yii::t('motion', 'support_finish_btn');
    echo '</button>';

    echo Html::endForm();
}


echo '</div>';

if (User::getCurrentUser() && !$motion->getPrivateComment(null, -1) && $consultation->getSettings()->showPrivateNotes) {
    ?>
    <div class="privateNoteOpener">
        <button class="btn btn-link btn-sm" tabindex="0">
            <span class="glyphicon glyphicon-pushpin" aria-hidden="true"></span>
            <?= Yii::t('motion', 'private_notes') ?>
        </button>
    </div>
    <?php
}

if ($hasPpAdminbox) {
    ?>
    <div class="proposedChangesOpener">
        <button class="btn btn-default btn-sm">
            <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
            <?= Yii::t('amend', 'proposal_open') ?>
        </button>
    </div>
    <?php
}
if ($hasPp) {
    if ($hasPpAdminbox) {
        echo $this->render('_set_proposed_procedure', [
            'motion' => $motion,
            'context'   => 'view',
            'msgAlert' => null,
        ]);
    }
    if ($motion->proposalFeedbackHasBeenRequested() && $motion->canSeeProposedProcedure($procedureToken)) {
        echo $this->render('_view_agree_to_proposal', ['motion' => $motion, 'procedureToken' => $procedureToken]);
    }
}

if ($motion->status === Motion::STATUS_DRAFT) {
    ?>
    <div class="content">
        <div class="alert alert-info alertDraft">
            <p><?= Yii::t('motion', 'info_draft_admin') ?></p>
        </div>
    </div>
    <?php
}

echo $this->render('@app/views/voting/_index_voting', ['assignedToMotion' => $motion]);

echo \app\models\layoutHooks\Layout::beforeMotionView($motion);

$viewText = $this->render('_view_text', [
    'motion'         => $motion,
    'commentForm'    => $commentForm,
    'openedComments' => $openedComments,
    'procedureToken' => $procedureToken
]);

$viewText = preg_replace_callback('/<!--PRIVATE_NOTE_(?<sectionId>\d+)_(?<paragraphNo>\d+)-->/iu', function ($matches) use ($motion) {
    return $this->render('_view_paragraph_private_note', [
        'motion'      => $motion,
        'sectionId'   => intval($matches['sectionId']),
        'paragraphNo' => intval($matches['paragraphNo']),
    ]);
}, $viewText);

$viewText = preg_replace_callback('/<!--AMENDMENT_LINK_(?<sectionId>\d+)_(?<paragraphNo>\d+)-->/iu', function ($matches) use ($motion) {
    if (!$motion->isCurrentlyAmendable(false, true)) {
        return '';
    }

    $imgUrl = \app\models\settings\AntragsgruenApp::getInstance()->resourceBase . 'img/aa-new-icon.svg';
    $title = '<img src="' . $imgUrl . '" alt="' . Yii::t('motion', 'amendment_create_paragraph') . '"></span>';
    $amendCreateUrl = UrlHelper::createUrl([
        '/amendment/create',
        'motionSlug' => $motion->getMotionSlug(),
        'sectionId' => $matches['sectionId'],
        'paragraphNo' => $matches['paragraphNo'],
    ]);
    return '<a class="btn btn-link btn-sm amendmentParaLink" href="' . Html::encode($amendCreateUrl) . '" title="' . Yii::t('motion', 'amendment_create_paragraph') . '"
                rel="nofollow">' . $title . '</a>';
}, $viewText);

echo $viewText;

$protocol = $motion->getProtocol();
if ($protocol && $protocol->status === \app\models\db\IAdminComment::TYPE_PROTOCOL_PUBLIC) {
    ?>
    <section class="protocolHolder section hidden">
        <h2 class="green"><?= Yii::t('motion', 'protocol') ?></h2>
        <div class="content">
            <?= $protocol->text ?>
        </div>
    </section>
    <?php
}

?>
    <form class="gotoLineNumerPanel form-inline">
        <div class="form-group">
            <label class="sr-only" for="gotoLineNumber">Line number</label>
            <div class="input-group">
                <div class="input-group-addon"><?= Yii::t('motion', 'goto_line') ?>:</div>
                <input type="number" name="lineNumber" id="gotoLineNumber" class="form-control">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="submit"><?= Yii::t('motion', 'goto_line_go') ?></button>
                </span>
            </div>
        </div>

        <span class="lineNumberNotFound hidden"><?= Yii::t('motion', 'goto_line_err') ?></span>
    </form>
<?php

$currUserId    = (User::getCurrentUser() ? User::getCurrentUser()->id : 0);
$supporters    = $motion->getSupporters(true);
$supportType   = $motion->getMyMotionType()->getMotionSupportTypeClass();
$supportPolicy = $motion->getMyMotionType()->getMotionSupportPolicy();

if (!$motion->isResolution()) {
    $loginlessSupported = MotionSupporter::getMyLoginlessSupportIds();
    echo LayoutHelper::printSupportingSection($motion, $supporters, $supportPolicy, $supportType, $loginlessSupported);
    echo LayoutHelper::printLikeDislikeSection($motion, $supportPolicy, $supportStatus);
}

echo \app\models\layoutHooks\Layout::afterMotionView($motion);

$amendments     = $motion->getVisibleAmendments();
$nobodyCanAmend = ($motion->getMyMotionType()->getAmendmentPolicy()->getPolicyID() === IPolicy::POLICY_NOBODY);
if (count($amendments) > 0 || (!$nobodyCanAmend && !$motion->isResolution())) {
    echo '<section class="amendments" aria-labelledby="amendmentsTitle">' .
         '<h2 class="green" id="amendmentsTitle">' . Yii::t('amend', 'amendments') . '</h2>
    <div class="content">';

    /** @noinspection PhpUnhandledExceptionInspection */
    if ($motion->isCurrentlyAmendable(false, true)) {
        echo '<div class="pull-right">';
        $title          = '<span class="icon glyphicon glyphicon-flash" aria-hidden="true"></span>';
        $title          .= Yii::t('motion', 'amendment_create');
        $amendCreateUrl = UrlHelper::createUrl(['amendment/create', 'motionSlug' => $motion->getMotionSlug()]);
        echo '<a class="btn btn-default btn-sm" href="' . Html::encode($amendCreateUrl) . '" rel="nofollow">' .
             $title . '</a>';
        echo '</div>';
    }

    echo \app\models\layoutHooks\Layout::getMotionFormattedAmendmentList($motion);

    echo '</div></section>';
}

$alternativeCommentView = \app\models\layoutHooks\Layout::getMotionAlternativeComments($motion);
if ($alternativeCommentView) {
    echo $alternativeCommentView;
}
$maySeeComments = $motion->getMyMotionType()->maySeeIComments();
if ($commentWholeMotions && $maySeeComments && !$motion->isResolution() && !$alternativeCommentView) {
    echo '<section class="comments" data-antragsgruen-widget="frontend/Comments" aria-labelledby="commentsTitle">';
    echo '<h2 class="green" id="commentsTitle">' . Yii::t('motion', 'comments') . '</h2>';
    $form           = $commentForm;
    $screeningAdmin = User::havePrivilege($motion->getMyConsultation(), Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::motion($motion));

    $screening = Yii::$app->session->getFlash('screening', null, true);
    if ($screening) {
        echo '<div class="content"><div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">' . Yii::t('base', 'aria_success') . ':</span>
                ' . Html::encode($screening) . '
            </div></div>';
    }

    if ($form === null || $form->paragraphNo != -1 || $form->sectionId != -1) {
        $form = new CommentForm($motion, null);
        $form->setDefaultData(-1, -1, User::getCurrentUser());
    }

    $screeningQueue = 0;
    foreach ($motion->comments as $comment) {
        if ($comment->status === MotionComment::STATUS_SCREENING && $comment->paragraph === -1) {
            $screeningQueue++;
        }
    }
    if ($screeningQueue > 0) {
        echo '<div class="commentScreeningQueue">';
        if ($screeningQueue === 1) {
            echo Yii::t('motion', 'comment_screen_queue_1');
        } else {
            echo str_replace('%NUM%', $screeningQueue, Yii::t('motion', 'comment_screen_queue_x'));
        }
        echo '</div>';
    }

    foreach ($motion->getVisibleComments($screeningAdmin, -1, null) as $comment) {
        /** @var MotionComment $comment */
        echo $this->render('@app/views/shared/comment', ['comment' => $comment]);
    }

    echo $form->renderFormOrErrorMessage();

    echo '</section>';
}

echo $this->render('_view_prevnext', ['motion' => $motion, 'top' => false, 'reducedNavigation' => $reducedNavigation]);
