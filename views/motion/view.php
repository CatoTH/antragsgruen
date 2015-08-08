<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\User;
use app\models\forms\CommentForm;
use app\models\policies\IPolicy;
use app\views\motion\LayoutHelper as MotionLayoutHelper;
use app\views\motion\LayoutHelper;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var Amendment[] $amendments
 * @var int[] $openedComments
 * @var string|null $adminEdit
 * @var null|string $supportStatus
 * @var null|CommentForm $commentForm
 * @var bool $commentWholeMotions
 * @var bool $consolidatedAmendments
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

if (isset($_REQUEST['backUrl']) && $_REQUEST['backTitle']) {
    $layout->addBreadcrumb($_REQUEST['backTitle'], $_REQUEST['backUrl']);
}
$layout->addBreadcrumb($motion->motionType->titleSingular);

$this->title = $motion->getTitleWithPrefix() . ' (' . $motion->consultation->title . ', Antragsgrün)';


$html        = '<ul class="sidebarActions">';
$sidebarRows = 0;

$policy = $motion->motionType->getAmendmentPolicy();
if ($policy->checkCurrUser(true, true)) {
    $html .= '<li class="amendmentCreate">';
    $amendCreateUrl = UrlHelper::createUrl(['amendment/create', 'motionId' => $motion->id]);
    $title          = '<span class="icon glyphicon glyphicon-flash"></span>';
    $title .= Yii::t('motion', 'Änderungsantrag stellen');
    $html .= Html::a($title, $amendCreateUrl) . '</li>';
    $sidebarRows++;
} else {
    $msg = $policy->getPermissionDeniedAmendmentMsg();
    if ($msg != '') {
        $html .= '<li class="amendmentCreate">';
        $html .= '<span style="font-style: italic;"><span class="icon glyphicon glyphicon-flash"></span>';
        $html .= Html::encode(Yii::t('motion', 'Änderungsantrag stellen'));
        $html .= '<br><span style="font-size: 13px; color: #dbdbdb; text-transform: none;">';
        $html .= Html::encode($msg) . '</span></span></li>';
        $sidebarRows++;
    }
}

if ($motion->motionType->getPDFLayoutClass() !== null && $motion->isVisible()) {
    $html .= '<li class="download">';
    $title = '<span class="icon glyphicon glyphicon-download-alt"></span>' .
        Yii::t('motion', 'PDF-Version');
    $html .= Html::a($title, UrlHelper::createMotionUrl($motion, 'pdf')) . '</li>';
    $sidebarRows++;
}

if ($motion->canMergeAmendments() && count($motion->amendments) > 0) {
    $html .= '<li class="mergeamendments">';
    $title = '<span class="icon glyphicon glyphicon-scissors"></span>' .
        Yii::t('motion', 'Änderungsanträge einpflegen');
    $html .= Html::a($title, UrlHelper::createMotionUrl($motion, 'mergeamendments')) . '</li>';
    $sidebarRows++;
}

if ($motion->canEdit()) {
    $html .= '<li class="edit">';
    $title = '<span class="icon glyphicon glyphicon-edit"></span>' .
        Yii::t('motion', 'Antrag bearbeiten');
    $html .= Html::a($title, UrlHelper::createMotionUrl($motion, 'edit')) . '</li>';
    $sidebarRows++;
}

if ($motion->canWithdraw()) {
    $html .= '<li class="withdraw">';
    $title = '<span class="icon glyphicon glyphicon-remove"></span>' .
        Yii::t('motion', 'Antrag zurückziehen');
    $html .= Html::a($title, UrlHelper::createMotionUrl($motion, 'withdraw')) . '</li>';
    $sidebarRows++;
}

if ($adminEdit) {
    $html .= '<li class="adminEdit">';
    $title = '<span class="icon glyphicon glyphicon-wrench"></span>' . 'Admin: bearbeiten';
    $html .= Html::a($title, $adminEdit) . '</li>';
    $sidebarRows++;
}

$html .= '<li class="back">';
$title = '<span class="icon glyphicon glyphicon-chevron-left"></span>' . 'Zurück zur Übersicht';
$html .= Html::a($title, UrlHelper::createUrl('consultation/index')) . '</li>';
$sidebarRows++;

$html .= '</ul>';
$layout->menusHtml[] = $html;

$minimalisticUi = $motion->consultation->getSettings()->minimalisticUI;
$minHeight      = $sidebarRows * 40 - 60;

echo '<h1>' . Html::encode($motion->getTitleWithPrefix()) . '</h1>';

echo '<div class="motionData" style="min-height: ' . $minHeight . 'px;">';

if (!$minimalisticUi) {
    echo '<div class="content">';

    if (!$motion->consultation->site->getSettings()->forceLogin) {
        $layout->loadShariff();
        $shariffBackend = UrlHelper::createUrl('consultation/shariffbackend');
        $myUrl          = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
        $lang           = Yii::$app->language;
        $dataTitle      = $motion->getTitleWithPrefix();
        echo '<div class="shariff" data-backend-url="' . Html::encode($shariffBackend) . '" data-theme="white"
           data-url="' . Html::encode($myUrl) . '"
           data-services="[&quot;twitter&quot;, &quot;facebook&quot;]"
           data-lang="' . Html::encode($lang) . '" data-title="' . Html::encode($dataTitle) . '"></div>';
    }

    /* @TODO
    if (count($antrag->antraege) > 0) { ?>
                <div class="alert alert-error" style="margin-top: 10px; margin-bottom: 25px;">
                    <?php if (count($antrag->antraege) == 1) {
                        echo 'Achtung: dies ist eine alte Fassung; die aktuelle Fassung gibt es hier:<br>';
                        $a = $antrag->antraege[0];
                        echo CHtml::link($a->revision_name . " - " . $a->name, $this->createUrl("antrag/anzeige",
    array("antrag_id" => $a->id)));
                    } else {
                        echo 'Achtung: dies ist eine alte Fassung. Aktuellere Fassungen gibt es hier:<br>';
                        foreach ($antrag->antraege as $a) {
                            echo "- " . CHtml::link($a->revision_name . " - " . $a->name, $this->createUrl(
    "antrag/anzeige", array("antrag_id" => $a->id))) . "<br>";
                        }
                    } ?>
                </div>
            <?php } */

    echo '<table class="motionDataTable">
                <tr>
                    <th>' . Yii::t('motion', 'Veranstaltung') . ':</th>
                    <td>' .
        Html::a($motion->consultation->title, UrlHelper::createUrl('consultation/index')) . '</td>
                </tr>';

    if ($motion->agendaItem) {
        echo '<tr><th>Tagesordnungspunkt:</th><td>';
        echo Html::encode($motion->agendaItem->code . ' ' . $motion->agendaItem->title);
        echo '</td></tr>';
    }

    $initiators = $motion->getInitiators();
    if (count($initiators) == 1) {
        echo '<tr><th>' . Yii::t('motion', 'AntragsstellerIn') . ':</th><td>';
    } else {
        echo '<tr><th>' . Yii::t('motion', 'AntragsstellerInnen') . ':</th><td>';
    }
    echo MotionLayoutHelper::formatInitiators($initiators, $controller->consultation);

    echo '</td></tr>
                <tr class="statusRow"><th>Status:</th><td>';

    $screeningMotionsShown = $motion->consultation->getSettings()->screeningMotionsShown;
    $statiNames            = Motion::getStati();
    if ($motion->status == Motion::STATUS_SUBMITTED_UNSCREENED) {
        echo '<span class="unscreened">' . Html::encode($statiNames[$motion->status]) . '</span>';
    } elseif ($motion->status == Motion::STATUS_SUBMITTED_SCREENED && $screeningMotionsShown) {
        echo '<span class="screened">Von der Programmkommission geprüft</span>';
    } else {
        echo Html::encode($statiNames[$motion->status]);
    }
    if (trim($motion->statusString) != '') {
        echo ' <small>(' . Html::encode($motion->statusString) . ')</string>';
    }
    echo '</td>
                </tr>';

    if ($motion->dateResolution != '') {
        echo '<tr><th>Entschieden am:</th>
       <td>' . Tools::formatMysqlDate($motion->dateResolution) . '</td>
     </tr>';
    }
    echo '<tr><th>Eingereicht:</th>
       <td>' . Tools::formatMysqlDateTime($motion->dateCreation) . '</td>
                </tr>';

    $admin = User::currentUserHasPrivilege($controller->consultation, User::PRIVILEGE_SCREENING);
    if ($admin && count($motion->consultation->tags) > 0) {
        echo '<tr><th>Themenbereiche:</th><td class="tags">';

        $tags         = [];
        $used_tag_ids = [];
        foreach ($motion->tags as $tag) {
            $used_tag_ids[] = $tag->id;
            $str            = Html::encode($tag->title);
            $str .= Html::beginForm('', 'post', ['class' => 'form-inline delTagForm delTag' . $tag->id]);
            $str .= '<input type="hidden" name="tagId" value="' . $tag->id . '">';
            $str .= '<button type="submit" name="motionDelTag">del</button>';
            $str .= Html::endForm();
            $tags[] = $str;
        }
        echo implode(', ', $tags);

        echo '&nbsp; &nbsp; <a href="#" class="tagAdderHolder">Neu</a>';
        echo Html::beginForm('', 'post', ['id' => 'tagAdderForm', 'class' => 'form-inline hidden']);
        echo '<select name="tagId" title="Thema aussuchen" class="form-control">
        <option>-</option>';

        foreach ($motion->consultation->tags as $tag) {
            if (!in_array($tag->id, $used_tag_ids)) {
                echo '<option value="' . IntVal($tag->id) . '">' . Html::encode($tag->title) . '</option>';
            }
        }
        echo '</select>
            <button class="btn btn-primary" type="submit" name="motionAddTag">Hinzufügen</button>';
        echo Html::endForm();
        echo '</td> </tr>';

    } elseif (count($motion->tags) > 0) {
        echo '<tr>
       <th>' . (count($motion->tags) > 1 ? 'Themenbereiche' : 'Themenbereich') . '</th>
       <td>';

        $tags = [];
        foreach ($motion->tags as $tag) {
            $tags[] = $tag->title;
        }
        echo Html::encode(implode(', ', $tags));

        echo '</td></tr>';
    }
    /* @TODO
    if ($motion->abgeleitetVon) {
                    ?>
                    <tr>
                        <th>Ersetzt diesen Antrag:</th>
                        <td><?php echo CHtml::link($antrag->abgeleitetVon->revision_name . " - " .
    $antrag->abgeleitetVon->name, $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->abgeleitetVon->id)));
    ?> </td>
                    </tr>
                <?php }
    */


    echo '</table>

    <div class="visible-xs-block">
        <div style="width: 49%; display: inline-block; text-align: center; padding-top: 25px;">
            <a href="' . Html::encode(UrlHelper::createMotionUrl($motion, 'pdf')) . '"
               class="btn" style="color: black;"><span class="glyphicon glyphicon-download-alt"></span> PDF-Version</a>
        </div>';

    $policy = $motion->motionType->getAmendmentPolicy();
    if ($policy->checkCurrUser(true, true)) {
        echo '<div style="width: 49%; display: inline-block; text-align: center; padding-top: 25px;">
            <a href="' . Html::encode(UrlHelper::createUrl(['amendment/create', 'motionId' => $motion->id])) . '"
               class="btn btn-danger" style="color: white;">';
        echo '<span class="icon glyphicon glyphicon-flash"></span>';
        echo Html::encode(Yii::t('motion', 'Änderungsantrag stellen')) . '</a>
        </div>';
    }
    echo '</div></div>';
}

echo $controller->showErrors();

echo '</div>';


foreach ($motion->getSortedSections(true) as $i => $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }
    echo '<section class="motionTextHolder motionTextHolder' . $i;
    if ($motion->consultation->getSettings()->lineLength > 80) {
        echo " smallFont";
    }
    echo '" id="section_' . $section->sectionId . '">';
    echo '<h3 class="green">' . Html::encode($section->consultationSetting->title) . '</h3>';

    $commOp = (isset($openedComments[$section->sectionId]) ? $openedComments[$section->sectionId] : []);
    echo $section->getSectionType()->showMotionView($controller, $commentForm, $commOp, $consolidatedAmendments);

    echo '</section>';
}


$currUserId = (\Yii::$app->user->isGuest ? 0 : \Yii::$app->user->id);
$supporters = $motion->getSupporters();

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

LayoutHelper::printSupportSection($motion, $motion->motionType->getSupportPolicy(), $supportStatus);


if (count($amendments) > 0 || $motion->motionType->getAmendmentPolicy()->getPolicyID() != IPolicy::POLICY_NOBODY) {
    echo '<section class="amendments"><h2 class="green">' . Yii::t('motion', 'Änderungsanträge') . '</h2>
    <div class="content">';

    if (count($amendments) > 0) {
        echo '<ul class="amendments">';
        foreach ($amendments as $amend) {
            echo '<li>';
            $aename = $amend->titlePrefix;
            if ($aename == "") {
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
        echo '<em>keine</em>';
    }

    echo '</div></section>';
}


if ($commentWholeMotions) {
    echo '<section class="comments"><h2 class="green">Kommentare</h2>';
    $form           = $commentForm;
    $screeningAdmin = User::currentUserHasPrivilege($motion->consultation, User::PRIVILEGE_SCREENING);

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
            echo '1 Kommentar wartet auf Freischaltung';
        } else {
            echo str_replace('%NUM%', $screeningQueue, '%NUM% Kommentare warten auf Freischaltung');
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
        LayoutHelper::showCommentForm($form, $motion->consultation, -1, -1);
    } elseif ($motion->motionType->getCommentPolicy()->checkCurrUser(true, true)) {
        echo '<div class="alert alert-info" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in"></span>
        Logge dich ein, um kommentieren zu können.
        </div>';
    }
    echo '</section>';
}

$layout->addOnLoadJS('$.Antragsgruen.motionShow();');
