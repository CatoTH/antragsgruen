<?php

use app\components\diff\AmendmentSectionFormatter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\AmendmentSection;
use app\models\db\User;
use app\models\forms\CommentForm;
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;
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
$consultation = $amendment->motion->consultation;

if (isset($_REQUEST['backUrl']) && $_REQUEST['backTitle']) {
    $layout->addBreadcrumb($_REQUEST['backTitle'], $_REQUEST['backUrl']);
    $layout->addBreadcrumb($amendment->getShortTitle());
} else {
    $motionUrl = UrlHelper::createMotionUrl($amendment->motion);
    $layout->addBreadcrumb($amendment->motion->motionType->titleSingular, $motionUrl);
    if (!$consultation->getSettings()->hideTitlePrefix && $amendment->titlePrefix != '') {
        $layout->addBreadcrumb($amendment->titlePrefix);
    } else {
        $layout->addBreadcrumb('Änderungsantrag');
    }
}

$this->title = $amendment->getTitle() . ' (' . $consultation->title . ', Antragsgrün)';


$html        = '<ul class="sidebarActions">';
$sidebarRows = 0;

if ($amendment->motion->motionType->getPDFLayoutClass() !== null && $amendment->isVisible()) {
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
    $title = '<span class="icon glyphicon glyphicon-wrench"></span>' . 'Admin: bearbeiten';
    $html .= Html::a($title, $adminEdit) . '</li>';
    $sidebarRows++;
}

$html .= '<li class="back">';
$title = '<span class="icon glyphicon glyphicon-chevron-left"></span>' . 'Zurück zum Antrag';
$html .= Html::a($title, UrlHelper::createMotionUrl($amendment->motion)) . '</li>';
$sidebarRows++;

$html .= '</ul>';
$layout->menusHtml[] = $html;


echo '<h1>' . Html::encode($amendment->getTitle()) . '</h1>';

$minHeight = $sidebarRows * 40 - 60;

echo '<div class="motionData" style="min-height: ' . $minHeight . 'px;"><div class="content">';

if (!$amendment->motion->consultation->site->getSettings()->forceLogin) {
    $layout->loadShariff();
    $shariffBackend = UrlHelper::createUrl('consultation/shariffbackend');
    $myUrl          = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment));
    $lang           = Yii::$app->language;
    $dataTitle      = $amendment->getTitle();
    echo '<div class="shariff" data-backend-url="' . Html::encode($shariffBackend) . '" data-theme="white"
           data-url="' . Html::encode($myUrl) . '"
           data-services="[&quot;twitter&quot;, &quot;facebook&quot;]"
           data-lang="' . Html::encode($lang) . '" data-title="' . Html::encode($dataTitle) . '"></div>';
}

echo '<table class="motionDataTable">
                <tr>
                    <th>' . Yii::t('amend', 'motion') . ':</th>
                    <td>' .
    Html::a($amendment->motion->title, UrlHelper::createMotionUrl($amendment->motion)) . '</td>
                </tr>
                <tr>
                    <th>' . Yii::t('amend', 'initiator'), ':</th>
                    <td>';

echo MotionLayoutHelper::formatInitiators($amendment->getInitiators(), $consultation);

echo '</td></tr>
                <tr class="statusRow"><th>Status:</th><td>';

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
    echo '<tr><th>Entschieden am:</th>
       <td>' . Tools::formatMysqlDate($amendment->dateResolution) . '</td>
     </tr>';
}
echo '<tr><th>Eingereicht:</th>
       <td>' . Tools::formatMysqlDateTime($amendment->dateCreation) . '</td>
                </tr>';
echo '</table>';

echo $controller->showErrors();

echo '</div>';
echo '</div>';

if ($amendment->changeEditorial != '') {
    echo '<section id="section_editorial" class="motionTextHolder">';
    echo '<h3 class="green">' . 'Redaktionelle Änderung' . '</h3>';
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
    echo '<h3 class="green">Begründung</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeExplanation;
    echo '</div></div>';
    echo '</section>';
}

$currUserId = (\Yii::$app->user->isGuest ? 0 : \Yii::$app->user->id);
$supporters = $amendment->getSupporters();
if (count($supporters) > 0) {
    echo '<section class="supporters"><h2 class="green">Unterstützer_Innen</h2>
    <div class="content">';

    if (count($supporters) > 0) {
        echo '<ul>';
        foreach ($supporters as $supp) {
            echo '<li>';
            if ($supp->id == $currUserId) {
                echo '<span class="label label-info">Du!</span> ';
            }
            echo Html::encode($supp->getNameWithOrga());
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<em>keine</em><br>';
    }
    echo "<br>";
    echo '</div></section>';
}

MotionLayoutHelper::printSupportSection($amendment, $amendment->motion->motionType->getSupportPolicy(), $supportStatus);

if ($amendment->motion->motionType->policyComments != IPolicy::POLICY_NOBODY) {
    echo '<section class="comments"><h2 class="green">Kommentare</h2>';

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
            echo '1 Kommentar wartet auf Freischaltung';
        } else {
            echo str_replace('%NUM%', $screeningQueue, '%NUM% Kommentare warten auf Freischaltung');
        }
        echo '</div>';
    }
    foreach ($amendment->comments as $comment) {
        if ($comment->paragraph == -1 && in_array($comment->status, $visibleStati)) {
            $commLink = UrlHelper::createAmendmentCommentUrl($comment);
            MotionLayoutHelper::showComment($comment, $screenAdmin, $baseLink, $commLink);
        }
    }

    if ($amendment->motion->motionType->getCommentPolicy()->checkCurrUser()) {
        MotionLayoutHelper::showCommentForm($form, $consultation, -1, -1);
    } elseif ($amendment->motion->motionType->getCommentPolicy()->checkCurrUser(true, true)) {
        echo '<div class="alert alert-info" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in"></span>
        Logge dich ein, um kommentieren zu können.
        </div>';
    }
    echo '</section>';
}

$layout->addOnLoadJS('$.Antragsgruen.amendmentShow();');
