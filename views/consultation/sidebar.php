<?php

use app\components\Tools;
use app\components\UrlHelper;
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

$html = Html::beginForm(UrlHelper::createUrl("consultation/search"), 'post', ['class' => 'hidden-xs form-search']);
$html .= '<div class="nav-list"><div class="nav-header">Suche</div>
    <div style="text-align: center; padding-left: 7px; padding-right: 7px;">
    <div class="input-group">
      <input type="text" class="form-control" name="query" placeholder="Suchbegriff...">
      <span class="input-group-btn">
        <button class="btn btn-default" type="submit">
            <span class="glyphicon glyphicon-search"></span> Suche
        </button>
      </span>
    </div>
    </div>
</div>';
$html .= Html::endForm();
$layout->menusHtml[] = $html;

$motionLink = $consultation->site->getBehaviorClass()->getSubmitMotionStr();
if ($motionLink != '') {
    $layout->preSidebarHtml = $motionLink;
} elseif (count($consultation->motionTypes) > 0 && (
        $consultation->getMotionPolicy()->checkCurUserHeuristically() ||
        $consultation->getMotionPolicy()->checkHeuristicallyAssumeLoggedIn()
    )
) {
    if (count($consultation->motionTypes) == 1) {
        $createLink = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $consultation->motionTypes[0]->id]);
        if ($consultation->getMotionPolicy()->checkCurUserHeuristically()) {
            $motionCreateLink = $createLink;
        } else {
            $motionCreateLink = UrlHelper::createUrl(['user/login', 'back' => $createLink]);
        }
        $layout->menusHtml[] = '<a class="createMotion" href="' . Html::encode($motionCreateLink) . '" ' .
            'title="' . Html::encode(Yii::t('con', 'Start a Motion')) . '"></a>';
    } else {
        $html = '<div><ul class="nav nav-list motions">';
        $html .= '<li class="nav-header">' . Yii::t('con', 'Create new...') . '</li>';
        foreach ($consultation->motionTypes as $motionType) {
            $createLink = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionType->id]);
            if ($consultation->getMotionPolicy()->checkCurUserHeuristically()) {
                $motionCreateLink = $createLink;
            } else {
                $motionCreateLink = UrlHelper::createUrl(['user/login', 'back' => $createLink]);
            }
            var_dump($motionCreateLink);
            $html .= '<li class="createMotion' . $motionType->id . '">';
            $html .= '<a href="' . Html::encode($motionCreateLink) . '">';
            $html .= Html::encode($motionType->title) . '</a></li>';
        }
        $html .= "</ul></div>";
        $layout->menusHtml[] = $html;
    }
}

if (!in_array($consultation->policyMotions, array("Admins", "Nobody"))) {
    $html = '<div><ul class="nav nav-list motions">';
    $html .= '<li class="nav-header">' . Yii::t('con', 'New Motions') . '</li>';
    if (count($newestMotions) == 0) {
        $html .= '<li><i>keine</i></li>';
    } else {
        foreach ($newestMotions as $motion) {
            $motionLink = UrlHelper::createUrl(['motion/view', 'motionId' => $motion->id]);
            $name       = '<span class="' . $motion->getIconCSSClass() . '"></span>' . Html::encode($motion->title);
            $html .= '<li>' . Html::a($name, $motionLink) . "</li>\n";
        }
    }
    $html .= "</ul></div>";
    $layout->menusHtml[] = $html;
}

if (!in_array($consultation->policyAmendments, array("Admins", "Nobody"))) {
    $html = '<div><ul class="nav nav-list amendments">';
    $html .= '<li class="nav-header">' . Yii::t('con', 'New Amendments') . '</li>';
    if (count($newestAmendments) == 0) {
        $html .= "<li><i>keine</i></li>";
    } else {
        foreach ($newestAmendments as $amendment) {
            $hideRev       = $consultation->getSettings()->hideRevision;
            $zu_str        = Html::encode(
                $hideRev ? $amendment->motion->title : $amendment->motion->titlePrefix
            );
            $amendmentLink = UrlHelper::createUrl(
                [
                    "amendment/view",
                    "amendmentId" => $amendment->id,
                    "motionId"    => $amendment->motion->id
                ]
            );
            $linkTitle     = '<span class="glyphicon glyphicon-flash"></span>';
            $linkTitle .= "<strong>" . Html::encode($amendment->titlePrefix) . "</strong> zu " . $zu_str;
            $html .= '<li>' . Html::a($linkTitle, $amendmentLink) . '</li>';
        }
    }
    $html .= "</ul></div>";
    $layout->menusHtml[] = $html;
}

if ($consultation->getMotionPolicy()->checkCurUserHeuristically() && count($consultation->motionTypes) == 1) {
    $newUrl = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $consultation->motionTypes[0]->id]);

    $layout->menusHtml[] = '<a class="createMotion" href="' . Html::encode($newUrl) . '"></a>';
}

if (!in_array($consultation->policyComments, array(0, 4))) {
    $html = "<div><ul class='nav nav-list'><li class='nav-header'>Neue Kommentare</li>";
    if (count($newestMotionComments) == 0) {
        $html .= "<li><i>keine</i></li>";
    } else {
        foreach ($newestMotionComments as $comment) {
            $commentLink = UrlHelper::createUrl(
                [
                    "motion/view",
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

$title = '<span class="glyphicon glyphicon-bell"></span>';
$title .= Yii::t('con', 'E-Mail-Benachrichtigung bei neuen Anträgen');
$link = UrlHelper::createUrl('consultation/notifications');
$html = "<div><ul class='nav nav-list'><li class='nav-header'>Benachrichtigungen</li>";
$html .= "<li class='notifications'>" . Html::a($title, $link) . "</li>";
$html .= "</ul></div>";

$layout->menusHtml[] = $html;


if ($consultation->getSettings()->showFeeds) {
    $feeds     = 0;
    $feedsHtml = "";
    if (!in_array($consultation->policyMotions, array("Admins", "Nobody"))) {
        $feedsHtml .= "<li>";
        $feedsHtml .= Html::a(Yii::t('con', 'Anträge'), UrlHelper::createUrl("consultation/feedmotions")) . "</li>";
        $feeds++;
    }
    if (!in_array($consultation->policyAmendments, array("Admins", "Nobody"))) {
        $feedsHtml .= "<li>";
        $feedsHtml .= Html::a(Yii::t('con', 'Änderungsanträge'), UrlHelper::createUrl("consultation/feedamendments"));
        $feedsHtml .= "</li>";
        $feeds++;
    }
    if (!in_array($consultation->policyComments, array(0, 4))) {
        $feedUrl = UrlHelper::createUrl("consultation/feedcomments");
        $feedsHtml .= "<li>" . Html::a(Yii::t('con', 'Kommentare'), $feedUrl) . "</li>";
        $feeds++;
    }
    if ($feeds > 1) {
        $feedAllUrl = UrlHelper::createUrl("consultation/feedall");
        $feedsHtml .= "<li>" . Html::a(Yii::t('con', 'Alles'), $feedAllUrl) . "</li>";
    }

    $feeds_str = ($feeds == 1 ? "Feed" : "Feeds");
    $html      = "<div><ul class='nav nav-list'><li class='nav-header'>";
    $html .= $feeds_str;
    $html .= "</li>" . $feedsHtml . "</ul></div>";

    $layout->menusHtml[] = $html;
}

if ($consultation->getSettings()->hasPDF) {
    $name = Yii::t('con', 'Alle PDFs zusammen');
    $html = "<div><ul class='nav nav-list'><li class='nav-header'>PDFs</li>";
    $html .= "<li class='pdf'>" . Html::a($name, UrlHelper::createUrl("consultation/pdfs")) . "</li>";
    if (!in_array($consultation->policyAmendments, array("Admins", "Nobody"))) {
        $amendmentPdfLink = UrlHelper::createUrl("consultation/amendmentpdfs");
        $linkTitle        = '<span class="glyphicon glyphicon-download-alt"></span>';
        $linkTitle .= Yii::t('con', 'Alle Änderungsanträge gesammelt');
        $html .= "<li>" . Html::a($linkTitle, $amendmentPdfLink) . "</li>";
    }
    $html .= "</ul></div>";
    $layout->menusHtml[] = $html;
}

if ($consultation->site->getBehaviorClass()->showAntragsgruenInSidebar()) {
    $layout->postSidebarHtml = "<div class='antragsgruenAd well'>
        <div class='nav-header'>Dein Antragsgrün</div>
        <div class='content'>
            Du willst Antragsgrün selbst für deine(n) KV / LV / GJ / BAG / LAG einsetzen?
            <div>
                <a href='" . Html::encode(UrlHelper::createUrl("manager/index")) . "' class='btn btn-primary'>
                <span class='glyphicon glyphicon-chevron-right'></span> Infos
                </a>
            </div>
        </div>
    </div>";
}
