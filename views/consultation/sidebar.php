<?php

use app\components\Tools;
use app\models\db\Amendment;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Motion[] $newestMotions
 * @var \app\models\db\User|null $myself
 * @var Amendment[] $newestAmendments
 * @var \app\models\db\MotionComment[] $newestMotionComments
 *
 */

/** @var \app\controllers\UserController $controller */
$controller   = $this->context;
$layout       = $controller->layoutParams;
$consultation = $controller->consultation;
$wording      = $consultation->getWording();

$html = Html::beginForm($controller->createUrl("consultation/search"), 'post', ['class' => 'hidden-xs form-search']);
$html .= '<div class="nav-list"><div class="nav-header">Suche</div>
    <div style="text-align: center;">
    <div class="input-append">
        <input class="search-query" type="search" name="suchbegriff" value="" autofocus placeholder="Suchbegriff...">
        <button type="submit" class="btn"><i style="height: 17px;" class="icon-search"></i></button>
    </div></div>
</div>';
$html .= Html::endForm();
$layout->menusHtml[] = $html;

$motionCreateLink = "";
if ($consultation->getMotionPolicy()->checkCurUserHeuristically()) {
    $motionCreateLink = $controller->createUrl("motion/create");
} elseif ($consultation->getMotionPolicy()->checkHeuristicallyAssumeLoggedIn()) {
    $motionCreateLink = $controller->createUrl(['user/login', 'back' => $controller->createUrl('motion/create')]);
}

$motionLink = $consultation->site->getBehaviorClass()->getSubmitMotionStr();
if ($motionLink != '') {
    $layout->preSidebarHtml = $motionLink;
} else {
    $layout->menusHtml[] = '<a class="neuer-antrag" href="' . Html::encode($motionCreateLink) . '" ' .
        'title="' . Html::encode($wording->get("Neuen Antrag stellen")) . '"></a>';
}

if (!in_array($consultation->policyMotions, array("Admins", "Nobody"))) {
    $html = '<div><ul class="nav nav-list neue-antraege">';
    $html .= '<li class="nav-header">' . $wording->get("Neue Anträge") . '</li>';
    if (count($newestMotions) == 0) {
        $html .= "<li><i>keine</i></li>";
    } else {
        foreach ($newestMotions as $motion) {
            $html .= "<li";
            /*
             * @TODO
            switch ($ant->typ) {
                case Motion::$TYP_ANTRAG:
                    $html .= " class='antrag'";
                    break;
                case Antrag::$TYP_RESOLUTION:
                    $html .= " class='resolution'";
                    break;
                default:
                    $html .= " class='resolution'";
            }
            */
            $motionLink = $controller->createUrl(['motion/show', 'motionId' => $motion->id]);
            $html .= '>' . Html::a($motion->title, $motionLink) . "</li>\n";
        }
    }
    $html .= "</ul></div>";
    $layout->menusHtml[] = $html;
}

if (!in_array($consultation->policyAmendments, array("Admins", "Nobody"))) {
    $html = '<div><ul class="nav nav-list neue-aenderungsantraege">';
    $html .= '<li class="nav-header">' . $wording->get("Neue Änderungsanträge") . '</li>';
    if (count($newestAmendments) == 0) {
        $html .= "<li><i>keine</i></li>";
    } else {
        foreach ($newestAmendments as $amendment) {
            $hideRev       = $consultation->getSettings()->hideRevision;
            $zu_str        = Html::encode(
                $hideRev ? $amendment->motion->title : $amendment->motion->titlePrefix
            );
            $amendmentLink = $controller->createUrl(
                [
                    "amendment/show",
                    "amendmentId" => $amendment->id,
                    "motionId"    => $amendment->motion->id
                ]
            );
            $linkTitle     = "<strong>" . Html::encode($amendment->titlePrefix) . "</strong> zu " . $zu_str;
            $html .= '<li class="aeantrag">' . Html::a($linkTitle, $amendmentLink) . '</li>';
        }
    }
    $html .= "</ul></div>";
    $layout->menusHtml[] = $html;
}

if ($consultation->getMotionPolicy()->checkCurUserHeuristically()) {
    $newUrl              = $controller->createUrl("motion/create");
    $layout->menusHtml[] = '<a class="neuer-antrag" href="' . Html::encode($newUrl) . '"></a>';
}

if (!in_array($consultation->policyComments, array(0, 4))) {
    $html = "<div><ul class='nav nav-list neue-kommentare'><li class='nav-header'>Neue Kommentare</li>";
    if (count($newestMotionComments) == 0) {
        $html .= "<li><i>keine</i></li>";
    } else {
        foreach ($newestMotionComments as $comment) {
            $commentLink = $controller->createUrl(
                [
                    "motion/show",
                    "motionId"  => $comment->motionId,
                    "commentId" => $comment->id,
                    "#"         => "comment" . $comment->id
                ]
            );
            $html .= "<li class='komm'>";
            $html .= "<strong>" . Html::encode($comment->user->name) . "</strong>, ";
            $html .= Tools::formatMysqlDateTime($comment->dateCreation);
            $html .= "<div>Zu " . Html::a($comment->motion->titlePrefix, $commentLink) . "</div>";
            $html .= "</li>\n";
        }
    }
    $html .= "</ul></div>";
    $layout->menusHtml[] = $html;
}


$title = $wording->get("E-Mail-Benachrichtigung bei neuen Anträgen");
$link  = $controller->createUrl('consultation/notifications');
$html  = "<div><ul class='nav nav-list neue-kommentare'><li class='nav-header'>Benachrichtigungen</li>";
$html .= "<li class='benachrichtigung'>" . Html::a($title, $link) . "</li>";
$html .= "</ul></div>";

$layout->menusHtml[] = $html;


if ($consultation->getSettings()->showFeeds) {
    $feeds     = 0;
    $feedsHtml = "";
    if (!in_array($consultation->policyMotions, array("Admins", "Nobody"))) {
        $feedsHtml .= "<li class='feed'>";
        $feedsHtml .= Html::a($wording->get("Anträge"), $controller->createUrl("consultation/feedmotions")) . "</li>";
        $feeds++;
    }
    if (!in_array($consultation->policyAmendments, array("Admins", "Nobody"))) {
        $feedsHtml .= "<li class='feed'>";
        $feedsHtml .= Html::a($wording->get("Änderungsanträge"), $controller->createUrl("consultation/feedamendments"));
        $feedsHtml .= "</li>";
        $feeds++;
    }
    if (!in_array($consultation->policyComments, array(0, 4))) {
        $feedUrl = $controller->createUrl("consultation/feedcomments");
        $feedsHtml .= "<li class='feed'>" . Html::a($wording->get("Kommentare"), $feedUrl) . "</li>";
        $feeds++;
    }
    if ($feeds > 1) {
        $feedAllUrl = $controller->createUrl("consultation/feedall");
        $feedsHtml .= "<li class='feed'>" . Html::a($wording->get("Alles"), $feedAllUrl) . "</li>";
    }

    $feeds_str = ($feeds == 1 ? "Feed" : "Feeds");
    $html      = "<div><ul class='nav nav-list neue-kommentare'><li class='nav-header'>";
    $html .= $feeds_str;
    $html .= "</li>" . $feedsHtml . "</ul></div>";

    $layout->menusHtml[] = $html;
}

if ($consultation->getSettings()->hasPDF) {
    $name = $wording->get("Alle PDFs zusammen");
    $html = "<div><ul class='nav nav-list neue-kommentare'><li class='nav-header'>PDFs</li>";
    $html .= "<li class='pdf'>" . Html::a($name, $controller->createUrl("consultation/pdfs")) . "</li>";
    if (!in_array($consultation->policyAmendments, array("Admins", "Nobody"))) {
        $amendmentPdfLink = $controller->createUrl("consultation/amendmentpdfs");
        $linkTitle        = "Alle " . $wording->get("Änderungsanträge") . " gesammelt";
        $html .= "<li class='pdf'>" . Html::a($linkTitle, $amendmentPdfLink) . "</li>";
    }
    $html .= "</ul></div>";
    $layout->menusHtml[] = $html;
}

if ($consultation->site->getBehaviorClass()->showAntragsgruenInSidebar()) {
    $layout->postSidebarHtml = "<div class='antragsgruen_werbung well'><div class='nav-list'>
        <div class='nav-header'>Dein Antragsgrün</div>
        <div class='content'>Du willst Antragsgrün selbst für deine(n) KV / LV / GJ / BAG / LAG einsetzen?
        <div class='myAntragsgruenAd'>
        <a href='" . Html::encode($controller->createUrl("manager/index")) . "' class='btn btn-primary'>
        <span class='icon-chevron-right'></span> Infos</a></div>
        </div>
        </div>";
    $layout->menusHtml[]     = $html;
}
