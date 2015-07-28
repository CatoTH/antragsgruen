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
    $layout->addBreadcrumb($amendment->titlePrefix);
}

$this->title = $amendment->getTitle() . ' (' . $consultation->title . ', Antragsgrün)';


$html        = '<ul class="sidebarActions">';
$sidebarRows = 0;

if ($amendment->motion->motionType->getPDFLayoutClass() !== null && $amendment->isVisible()) {
    $html .= '<li class="download">';
    $title = '<span class="icon glyphicon glyphicon-download-alt"></span>' .
        Yii::t('motion', 'PDF-Version herunterladen');
    $html .= Html::a($title, UrlHelper::createAmendmentUrl($amendment, 'pdf')) . '</li>';
    $sidebarRows++;
}


if ($amendment->canEdit()) {
    $html .= '<li class="edit">';
    $title = '<span class="icon glyphicon glyphicon-edit"></span>' .
        Yii::t('motion', 'Änderungsantrag bearbeiten');
    $html .= Html::a($title, UrlHelper::createAmendmentUrl($amendment, 'edit')) . '</li>';
    $sidebarRows++;
}

if ($amendment->canWithdraw()) {
    $html .= '<li class="withdraw">';
    $title = '<span class="icon glyphicon glyphicon-remove"></span>' .
        Yii::t('motion', 'Änderungsantrag zurückziehen');
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
echo '<table class="motionDataTable">
                <tr>
                    <th>' . Yii::t('amend', 'Antrag') . ':</th>
                    <td>' .
    Html::a($amendment->motion->title, UrlHelper::createMotionUrl($amendment->motion)) . '</td>
                </tr>
                <tr>
                    <th>' . Yii::t('amend', 'AntragsstellerIn'), ':</th>
                    <td>';

echo MotionLayoutHelper::formatInitiators($amendment->getInitiators(), $consultation);

echo '</td></tr>
                <tr class="statusRow"><th>Status:</th><td>';

$screeningMotionsShown = $consultation->getSettings()->screeningMotionsShown;
$statiNames            = Amendment::getStati();
if ($amendment->status == Amendment::STATUS_SUBMITTED_UNSCREENED) {
    echo '<span class="unscreened">' . Html::encode($statiNames[$amendment->status]) . '</span>';
} elseif ($amendment->status == Amendment::STATUS_SUBMITTED_SCREENED && $screeningMotionsShown) {
    echo '<span class="screened">Von der Programmkommission geprüft</span>';
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
echo '</div>';
echo '</div>';

/** @var AmendmentSection[] $sections */
$sections = $amendment->getSortedSections(false);
foreach ($sections as $section) {
    if ($section->consultationSetting->type == ISectionType::TYPE_TEXT_SIMPLE) {
        $formatter  = new AmendmentSectionFormatter($section, \app\components\diff\Diff::FORMATTING_CLASSES);
        $diffGroups = $formatter->getGroupedDiffLinesWithNumbers();

        if (count($diffGroups) > 0) {
            echo '<section id="section_' . $section->sectionId . '" class="motionTextHolder">';
            echo '<h3 class="green">' . Html::encode($section->consultationSetting->title) . '</h3>';
            echo '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';
            $wrapStart = '<section class="paragraph"><div class="text">';
            $wrapEnd   = '</div></section>';
            $firstLine = $section->getFirstLineNumber();
            $html      = TextSimple::formatDiffGroup($diffGroups, $wrapStart, $wrapEnd, $firstLine);
            echo str_replace('###FORCELINEBREAK###', '<br>', $html);
            echo '</div>';
            echo '</section>';
        }
    } elseif ($section->consultationSetting->type == ISectionType::TYPE_TITLE) {
        if ($section->data == $section->getOriginalMotionSection()->data) {
            continue;
        }
        echo '<section id="section_title" class="motionTextHolder">';
        echo '<h3 class="green">' . Html::encode($section->consultationSetting->title) . '</h3>';
        echo '<div id="section_title_0" class="paragraph"><div class="text">';
        echo '<h4 class="lineSummary">' . 'Ändern in' . ':</h4>';
        echo '<p>' . Html::encode($section->data) . '</p>';
        echo '</div></div></section>';
    }
}


if ($amendment->changeExplanation != '') {
    echo '<section id="amendmentExplanation" class="motionTextHolder">';
    echo '<h3 class="green">Begründung</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeExplanation;
    echo '</div></div>';
    echo '</section>';
}

if ($amendment->motion->motionType->policyComments != IPolicy::POLICY_NOBODY) {
    echo '<section class="comments"><h2 class="green">Kommentare</h2>';

    $form    = $commentForm;
    $imadmin = User::currentUserHasPrivilege($consultation, User::PRIVILEGE_SCREENING);

    if ($form === null || $form->paragraphNo != -1 || $form->sectionId != -1) {
        $form              = new \app\models\forms\CommentForm();
        $form->paragraphNo = -1;
        $form->sectionId   = -1;
    }

    $baseLink = UrlHelper::createAmendmentUrl($amendment);
    foreach ($amendment->comments as $comment) {
        if ($comment->paragraph == -1 && $comment->status != AmendmentComment::STATUS_DELETED) {
            $commLink = UrlHelper::createAmendmentCommentUrl($comment);
            MotionLayoutHelper::showComment($comment, $imadmin, $baseLink, $commLink);
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

if (!$consultation->site->getSettings()->forceLogin) {
    // @TODO Social Sharing
}
$layout->addOnLoadJS('$.Antragsgruen.amendmentShow();');
