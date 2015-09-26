<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\ConsultationMotionType;
use app\models\db\Motion;
use app\models\policies\IPolicy;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Motion[] $newestMotions
 * @var \app\models\db\User|null $myself
 * @var Amendment[] $newestAmendments
 * @var \app\models\db\IComment[] $newestComments
 */

/** @var \app\controllers\UserController $controller */
$controller   = $this->context;
$layout       = $controller->layoutParams;
$consultation = $controller->consultation;


$hasComments   = false;
$hasMotions    = false;
$hasAmendments = false;
$hasPDF        = false;
foreach ($consultation->motionTypes as $type) {
    if ($type->policyComments != IPolicy::POLICY_NOBODY) {
        $hasComments = true;
    }
    if ($type->policyMotions != IPolicy::POLICY_NOBODY) {
        $hasMotions = true;
    }
    if ($type->policyAmendments != IPolicy::POLICY_NOBODY) {
        $hasAmendments = true;
    }
    if ($type->getPDFLayoutClass() !== null) {
        $hasPDF = true;
    }
}

$html = Html::beginForm(UrlHelper::createUrl("consultation/search"), 'post', ['class' => 'hidden-xs form-search']);
$html .= '<div class="nav-list"><div class="nav-header">Suche</div>
    <div style="text-align: center; padding-left: 7px; padding-right: 7px;">
    <div class="input-group">
      <input type="text" class="form-control query" name="query"
        placeholder="Suchbegriff" required title="Suchbegriff">
      <span class="input-group-btn">
        <button class="btn btn-default" type="submit" title="Suche">
            <span class="glyphicon glyphicon-search"></span> Suche
        </button>
      </span>
    </div>
    </div>
</div>';
$html .= Html::endForm();
$layout->menusHtml[] = $html;

$showCreate = true;
if ($consultation->getSettings()->getStartLayoutView() == 'index_layout_agenda') {
    foreach ($consultation->agendaItems as $item) {
        if ($item->motionType) {
            $showCreate = false;
        }
    }
}
if ($showCreate) {
    $motionTypes = $consultation->motionTypes;
    $motionLink  = $consultation->site->getBehaviorClass()->getSubmitMotionStr();
    $working = [];
    foreach ($motionTypes as $motionType) {
        if ($motionType->getMotionPolicy()->checkCurrUser(false, true)) {
            $working[] = $motionType;
        }
    }

    if ($motionLink != '') {
        $layout->preSidebarHtml = $motionLink;
    } elseif (count($working) > 0) {
        /** @var ConsultationMotionType[] $working */
        if (count($working) == 1) {
            if ($working[0]->getMotionPolicy()->checkCurrUser(false, true)) {
                $link = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionTypes[0]->id]);
                $description = Html::encode(Yii::t('con', 'Start a Motion'));
                $layout->menusHtml[] = '<div class="createMotionHolder1"><div class="createMotionHolder2">' .
                    '<a class="createMotion" href="' . Html::encode($link) . '" title="' . $description . '">' .
                    '<span class="glyphicon glyphicon-plus-sign"></span>' . $description .
                    '</a></div></div>';
            }
        } else {
            $html = '<div><ul class="nav nav-list motions">';
            $html .= '<li class="nav-header">' . Yii::t('con', 'Create new...') . '</li>';
            foreach ($working as $motionType) {
                if ($motionType->getMotionPolicy()->checkCurrUser(false, true)) {
                    $motionCreateLink = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionType->id]);
                    $html .= '<li class="createMotion' . $motionType->id . '">';
                    $html .= '<a href="' . Html::encode($motionCreateLink) . '">';
                    $html .= Html::encode($motionType->titleSingular) . '</a></li>';
                }
            }
            $html .= "</ul></div>";
            $layout->menusHtml[] = $html;
        }
    }
}

if ($hasMotions) {
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

if ($hasAmendments) {
    $html = '<div><ul class="nav nav-list amendments">';
    $html .= '<li class="nav-header">' . Yii::t('con', 'New Amendments') . '</li>';
    if (count($newestAmendments) == 0) {
        $html .= "<li><i>keine</i></li>";
    } else {
        foreach ($newestAmendments as $amendment) {
            $title = explode(' ', Html::encode($amendment->getShortTitle()));
            if (count($title) > 1) {
                $title[0] = '<strong>' . Html::encode($title[0]) . '</strong>';
            }
            $title = implode(' ', $title);
            $amendmentLink = UrlHelper::createUrl(
                [
                    'amendment/view',
                    'amendmentId' => $amendment->id,
                    'motionId'    => $amendment->motion->id
                ]
            );
            $linkTitle     = '<span class="glyphicon glyphicon-flash"></span>' . $title;
            $html .= '<li>' . Html::a($linkTitle, $amendmentLink, ['class' => 'amendment' . $amendment->id]) . '</li>';
        }
    }
    $html .= "</ul></div>";
    $layout->menusHtml[] = $html;
}


if ($consultation->getSettings()->getStartLayoutView() != 'index_layout_agenda') {
    /** @var ConsultationMotionType[] $motionTypes */
    if (count($motionTypes) == 1 && $motionTypes[0]->getMotionPolicy()->checkCurrUser(false, true)) {
        $newUrl = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionTypes[0]->id]);
        $description = Html::encode(Yii::t('con', 'Start a Motion'));
        $layout->menusHtml[] = '<div class="createMotionHolder1"><div class="createMotionHolder2">' .
            '<a class="createMotion" href="' . Html::encode($newUrl) . '" title="' . $description . '">' .
            '<span class="glyphicon glyphicon-plus-sign"></span>' . $description .
            '</a></div></div>';
    }
}


if ($hasComments) {
    $html = '<div><ul class="nav nav-list comments"><li class="nav-header">Neue Kommentare</li>';
    if (count($newestComments) == 0) {
        $html .= "<li><i>keine</i></li>";
    } else {
        foreach ($newestComments as $comment) {
            $html .= '<li><a href="' . Html::encode($comment->getLink()) . '">';
            $html .= '<span class="glyphicon glyphicon-comment"></span>';
            $html .= '<strong>' . Html::encode($comment->name) . '</strong>, ';
            $html .= Tools::formatMysqlDateTime($comment->dateCreation);
            if (is_a($comment, \app\models\db\MotionComment::class)) {
                /** @var \app\models\db\MotionComment $comment */
                $html .= '<div>Zu ' . Html::encode($comment->motion->titlePrefix) . '</div>';
            } elseif (is_a($comment, \app\models\db\AmendmentComment::class)) {
                /** @var \app\models\db\AmendmentComment $comment */
                $html .= '<div>Zu ' . Html::encode($comment->amendment->titlePrefix) . '</div>';
            }
            $html .= '</a></li>';
        }
    }
    $html .= '</ul></div>';
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

    if ($hasMotions) {
        $feedUrl = UrlHelper::createUrl('consultation/feedmotions');
        $feedsHtml .= "<li>";
        $feedsHtml .= Html::a(Yii::t('con', 'Anträge'), $feedUrl, ['class' => 'feedMotions']) . "</li>";
        $feeds++;
    }

    if ($hasAmendments) {
        $feedUrl = UrlHelper::createUrl('consultation/feedamendments');
        $feedsHtml .= "<li>";
        $feedsHtml .= Html::a(Yii::t('con', 'Änderungsanträge'), $feedUrl, ['class' => 'feedAmendments']);
        $feedsHtml .= "</li>";
        $feeds++;
    }

    if ($hasComments) {
        $feedUrl = UrlHelper::createUrl('consultation/feedcomments');
        $feedsHtml .= "<li>" . Html::a(Yii::t('con', 'Kommentare'), $feedUrl, ['class' => 'feedComments']) . "</li>";
        $feeds++;
    }

    if ($feeds > 1) {
        $feedUrl = UrlHelper::createUrl('consultation/feedall');
        $feedsHtml .= "<li>" . Html::a(Yii::t('con', 'Alles'), $feedUrl, ['class' => 'feedAll']) . "</li>";
    }

    $feeds_str = ($feeds == 1 ? "Feed" : "Feeds");
    $html      = "<div><ul class='nav nav-list'><li class='nav-header'>";
    $html .= $feeds_str;
    $html .= "</li>" . $feedsHtml . "</ul></div>";

    $layout->menusHtml[] = $html;
}

if ($hasPDF) {
    $name    = '<span class="glyphicon glyphicon-download-alt"></span>' . Yii::t('con', 'Alle PDFs zusammen');
    $pdfLink = UrlHelper::createUrl('motion/pdfcollection');
    $html    = '<div><ul class="nav nav-list"><li class="nav-header">PDFs</li>';
    $html .= '<li>' . Html::a($name, $pdfLink, ['class' => 'motionPdfCompilation']) . '</li>';

    if ($hasAmendments) {
        $amendmentPdfLink = UrlHelper::createUrl('amendment/pdfcollection');
        $linkTitle        = '<span class="glyphicon glyphicon-download-alt"></span>';
        $linkTitle .= Yii::t('con', 'Alle Änderungsanträge gesammelt');
        $html .= '<li>' . Html::a($linkTitle, $amendmentPdfLink, ['class' => 'amendmentPdfs']) . '</li>';
    }

    $html .= '</ul></div>';
    $layout->menusHtml[] = $html;
}

if ($consultation->site->getSettings()->showAntragsgruenAd) {
    $layout->postSidebarHtml = '<div class="antragsgruenAd well">
        <div class="nav-header">Dein Antragsgrün</div>
        <div class="content">
            Du willst Antragsgrün selbst für deine(n) KV / LV / GJ / BAG / LAG einsetzen?
            <div>
                <a href="' . Html::encode(UrlHelper::createUrl('manager/index')) . '" ' .
        'title="Das Antragstool selbst einsetzen" class="btn btn-primary">
                <span class="glyphicon glyphicon-chevron-right"></span> Infos
                </a>
            </div>
        </div>
    </div>';
}
