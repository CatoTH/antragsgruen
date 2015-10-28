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

/** @var \app\controllers\Base $controller */
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

$html = Html::beginForm(UrlHelper::createUrl("consultation/search"), 'post', ['class' => 'form-search']);
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
    $working     = [];
    foreach ($motionTypes as $motionType) {
        if ($motionType->getMotionPolicy()->checkCurrUser(false, true)) {
            $working[] = $motionType;
        }
    }

    if (count($working) > 0) {
        /** @var ConsultationMotionType[] $working */
        if (count($working) == 1) {
            if ($working[0]->getMotionPolicy()->checkCurrUser(false, true)) {
                $link                         = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionTypes[0]->id]);
                $description                  = Html::encode(Yii::t('con', 'start_motion'));
                $layout->menusHtml[]          = '<div class="createMotionHolder1"><div class="createMotionHolder2">' .
                    '<a class="createMotion" href="' . Html::encode($link) . '" title="' . $description . '">' .
                    '<span class="glyphicon glyphicon-plus-sign"></span>' . $description .
                    '</a></div></div>';
                $layout->menusSmallAttachment = '<a class="navbar-brand" href="' . Html::encode($link) . '">' .
                    '<span class="glyphicon glyphicon-plus-sign"></span>' . $description . '</a>';
            }
        } else {
            $html = '<div><ul class="nav nav-list motions">';
            $html .= '<li class="nav-header">' . Yii::t('con', 'create_new') . '</li>';
            $htmlSmall = '<li class="dropdown">
      <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
      aria-expanded="false">' . Yii::t('con', 'create_new') . ' <span class="caret"></span></a>
                    <ul class="dropdown-menu">';
            foreach ($working as $motionType) {
                if ($motionType->getMotionPolicy()->checkCurrUser(false, true)) {
                    $motionCreateLink = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionType->id]);
                    $html .= '<li class="createMotion' . $motionType->id . '">';
                    $html .= '<a href="' . Html::encode($motionCreateLink) . '">';
                    $html .= Html::encode($motionType->titleSingular) . '</a></li>';

                    $htmlSmall .= '<li class="createMotion' . $motionType->id . '">';
                    $htmlSmall .= '<a href="' . Html::encode($motionCreateLink) . '">';
                    $htmlSmall .= Html::encode($motionType->titleSingular) . '</a></li>';
                }
            }
            $html .= '</ul></div>';
            $htmlSmall .= '</ul></li>';
            $layout->menusHtml[]      = $html;
            $layout->menusHtmlSmall[] = $htmlSmall;
        }
    }
}

if ($hasMotions) {
    $html = '<div><ul class="nav nav-list motions">';
    $html .= '<li class="nav-header">' . Yii::t('con', 'new_motions') . '</li>';
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
    $html .= '<li class="nav-header">' . Yii::t('con', 'new_amendments') . '</li>';
    if (count($newestAmendments) == 0) {
        $html .= "<li><i>keine</i></li>";
    } else {
        foreach ($newestAmendments as $amendment) {
            $title = explode(' ', Html::encode($amendment->getShortTitle()));
            if (count($title) > 1) {
                $title[0] = '<strong>' . Html::encode($title[0]) . '</strong>';
            }
            $title         = implode(' ', $title);
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
        $newUrl                       = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionTypes[0]->id]);
        $description                  = Html::encode(Yii::t('con', 'start_motion'));
        $layout->menusHtml[]          = '<div class="createMotionHolder1"><div class="createMotionHolder2">' .
            '<a class="createMotion" href="' . Html::encode($newUrl) . '" title="' . $description . '">' .
            '<span class="glyphicon glyphicon-plus-sign"></span>' . $description .
            '</a></div></div>';
        $layout->menusSmallAttachment = '<a class="navbar-brand" href="' . Html::encode($newUrl) . '">' .
            '<span class="glyphicon glyphicon-plus-sign"></span>' . $description . '</a>';
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
$title .= Yii::t('con', 'email_notifications');
$link = UrlHelper::createUrl('consultation/notifications');
$html = '<div><ul class="nav nav-list"><li class="nav-header">' .
    Yii::t('con', 'notifications') . '</li>';
$html .= '<li class="notifications">' . Html::a($title, $link) . '</li>';
$html .= '</ul></div>';
$layout->menusHtml[]      = $html;
$layout->menusHtmlSmall[] = '<li>' . Html::a(Yii::t('con', 'notifications'), $link) . '</li>';


if ($consultation->getSettings()->showFeeds) {
    $feeds          = 0;
    $feedsHtml      = '';
    $feedsHtmlSmall = '<li class="dropdown">
      <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
      aria-expanded="false">' . 'Feeds' . ' <span class="caret"></span></a>
                    <ul class="dropdown-menu">';

    if ($hasMotions) {
        $feedUrl = UrlHelper::createUrl('consultation/feedmotions');
        $link    = Html::a(Yii::t('con', 'feed_motions'), $feedUrl, ['class' => 'feedMotions']);
        $feedsHtml .= '<li>' . $link . '</li>';
        $feedsHtmlSmall .= '<li>' . $link . '</li>';
        $feeds++;
    }

    if ($hasAmendments) {
        $feedUrl = UrlHelper::createUrl('consultation/feedamendments');
        $link    = Html::a(Yii::t('con', 'feed_amendments'), $feedUrl, ['class' => 'feedAmendments']);
        $feedsHtml .= '<li>' . $link . '</li>';
        $feedsHtmlSmall .= '<li>' . $link . '</li>';
        $feeds++;
    }

    if ($hasComments) {
        $feedUrl = UrlHelper::createUrl('consultation/feedcomments');
        $link    = Html::a(Yii::t('con', 'feed_comments'), $feedUrl, ['class' => 'feedComments']);
        $feedsHtml .= '<li>' . $link . '</li>';
        $feedsHtmlSmall .= '<li>' . $link . '</li>';
        $feeds++;
    }

    if ($feeds > 1) {
        $feedUrl = UrlHelper::createUrl('consultation/feedall');
        $link    = Html::a(Yii::t('con', 'feed_all'), $feedUrl, ['class' => 'feedAll']);
        $feedsHtml .= '<li>' . $link . '</li>';
        $feedsHtmlSmall .= '<li>' . $link . '</li>';
    }

    $feeds_str = ($feeds == 1 ? Yii::t('con', 'feed') : Yii::t('con', 'feeds'));
    $html      = '<div><ul class="nav nav-list"><li class="nav-header">';
    $html .= $feeds_str;
    $html .= '</li>' . $feedsHtml . '</ul></div>';
    $feedsHtmlSmall .= '</ul></li>';

    $layout->menusHtmlSmall[] = $feedsHtmlSmall;
    $layout->menusHtml[]      = $html;
}

if ($hasPDF) {
    $name    = '<span class="glyphicon glyphicon-download-alt"></span>' . Yii::t('con', 'pdf_all');
    $pdfLink = UrlHelper::createUrl('motion/pdfcollection');
    $html    = '<div><ul class="nav nav-list"><li class="nav-header">PDFs</li>';
    $html .= '<li>' . Html::a($name, $pdfLink, ['class' => 'motionPdfCompilation']) . '</li>';

    $link = Html::a(Yii::t('con', 'pdf_motions'), $pdfLink, ['class' => 'motionPdfCompilation']);
    $layout->menusHtmlSmall[] = '<li>' . $link . '</li>';

    if ($hasAmendments) {
        $amendmentPdfLink = UrlHelper::createUrl('amendment/pdfcollection');
        $linkTitle        = '<span class="glyphicon glyphicon-download-alt"></span>';
        $linkTitle .= Yii::t('con', 'pdf_amendments');
        $html .= '<li>' . Html::a($linkTitle, $amendmentPdfLink, ['class' => 'amendmentPdfs']) . '</li>';

        $link = Html::a(Yii::t('con', 'pdf_amendments_small'), $amendmentPdfLink, ['class' => 'amendmentPdfs']);
        $layout->menusHtmlSmall[] = '<li>' . $link . '</li>';
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
                <a href="https://antragsgruen.de/" title="Das Antragstool selbst einsetzen" class="btn btn-primary">
                <span class="glyphicon glyphicon-chevron-right"></span> Infos
                </a>
            </div>
        </div>
    </div>';
}
