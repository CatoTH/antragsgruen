<?php

use app\models\policies\Nobody;
use app\components\{HTMLTools, Tools, UrlHelper};
use app\models\db\{Amendment, ConsultationMotionType, Motion, VotingBlock};
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
$settings = $consultation->getSettings();

$hasComments   = false;
$hasMotions    = false;
$hasAmendments = false;
$hasPDF        = false;

/** @var ConsultationMotionType[] $pinkButtonCreates */
$pinkButtonCreates = [];
/** @var ConsultationMotionType[] $creatableTypes */
$creatableTypes = [];

foreach ($consultation->motionTypes as $type) {
    if (!is_a($type->getCommentPolicy(), Nobody::class)) {
        $hasComments = true;
    }
    if (!is_a($type->getMotionPolicy(), Nobody::class)) {
        $hasMotions = true;
    }
    if (!is_a($type->getAmendmentPolicy(), Nobody::class)) {
        $hasAmendments = true;
    }
    if ($type->hasPdfLayout()) {
        $hasPDF = true;
    }

    if ($type->amendmentsOnly) {
        $creatable = (count($type->getAmendableOnlyMotions(false, true)) > 0);
    } else {
        $creatable = $type->getMotionPolicy()->checkCurrUserMotion(false, true);
    }
    if ($creatable && $type->sidebarCreateButton) {
        $pinkButtonCreates[] = $type;
    } elseif ($creatable) {
        $creatableTypes[] = $type;
    }
}

$layout->menuSidebarType = \app\models\settings\Layout::SIDEBAR_TYPE_CONSULTATION;
$layout->menusHtml[] = \app\models\layoutHooks\Layout::getSearchForm();

$showCreate = true;
if ($settings->getStartLayoutView() === 'index_layout_agenda') {
    foreach ($consultation->agendaItems as $item) {
        if ($item->getMyMotionType()) {
            $showCreate = false;
        }
    }
}
if ($showCreate || count($pinkButtonCreates) > 0) {
    \app\models\layoutHooks\Layout::setSidebarCreateMotionButton($pinkButtonCreates);

    if (count($creatableTypes) > 0) {
        $html      = '<section class="sidebar-box" aria-labelledby="sidebarCreateNewTitle"><ul class="nav nav-list motions createMotionList">';
        $html      .= '<li class="nav-header" id="sidebarCreateNewTitle">' . Yii::t('con', 'create_new') . '</li>';
        $htmlSmall = '<section><h2>' . Yii::t('con', 'create_new') . '</h2><ul>';
        foreach ($creatableTypes as $creatableType) {
            $motionCreateLink = $creatableType->getCreateLink(false, true);

            $html .= '<li class="createMotion' . $creatableType->id . '">';
            $html .= '<a href="' . Html::encode($motionCreateLink) . '" rel="nofollow">';
            $html .= Html::encode($creatableType->titleSingular) . '</a></li>';

            $htmlSmall .= '<li class="createMotion' . $creatableType->id . '">';
            $htmlSmall .= '<a href="' . Html::encode($motionCreateLink) . '" rel="nofollow">';
            $htmlSmall .= Html::encode($creatableType->titleSingular) . '</a></li>';
        }
        $html                     .= '</ul></section>';
        $htmlSmall                .= '</ul></section>';
        $layout->menusHtml[]      = $html;
        $layout->menusHtmlSmall[] = $htmlSmall;
    }
}


$html = '<section class="sidebar-box" aria-labelledby="sidebarNewsTitle"><ul class="nav nav-list"><li class="nav-header" id="sidebarNewsTitle">' .
    Yii::t('con', 'news') . '</li>';
$htmlSmall = '<section><h2>' . Yii::t('con', 'news') . '</h2><ul>';

$title = '<span class="fontello fontello-globe"></span>' . Yii::t('con', 'activity_log');
$link  = UrlHelper::createUrl('consultation/activitylog');
$html  .= '<li class="activitylog">' . Html::a($title, $link) . '</li>';
$htmlSmall  .= '<li>' . Html::a(Yii::t('con', 'activity_log'), $link) . '</li>';

$title = '<span class="glyphicon glyphicon-bell" aria-hidden="true"></span>' . Yii::t('con', 'email_notifications');
$link  = UrlHelper::createUrl('consultation/notifications');
$html  .= '<li class="notifications">' . Html::a($title, $link) . '</li>';
$htmlSmall .= '<li>' . Html::a(Yii::t('con', 'email_notifications'), $link) . '</li>';

$title = '<span class="fontello fontello-rss-squared" aria-hidden="true"></span>' . Yii::t('con', 'feeds');
$link  = UrlHelper::createUrl('consultation/feeds');
$html  .= '<li class="feeds">' . Html::a($title, $link) . '</li>';
$htmlSmall .= '<li>' . Html::a(Yii::t('con', 'feeds'), $link) . '</li>';

if ($settings->collectingPage) {
    $title = '<span class="glyphicon glyphicon-file" aria-hidden="true"></span>' . Yii::t('con', 'sb_collecting');
    $link  = UrlHelper::createUrl('consultation/collecting');
    $html  .= '<li class="collecting">' . Html::a($title, $link) . '</li>';
    $htmlSmall .= '<li>' . Html::a(Yii::t('con', 'sb_collecting'), $link) . '</li>';
}

$html .= '</ul></section>';
$htmlSmall .= '</ul></section>';
$layout->menusHtml[]      = $html;
$layout->menusHtmlSmall[] = $htmlSmall;

$closedVotings = VotingBlock::getPublishedClosedVotings($consultation);
if ($settings->proposalProcedurePage || count($closedVotings) > 0 || $settings->startLayoutResolutions !== \app\models\settings\Consultation::START_LAYOUT_RESOLUTIONS_ABOVE) {
    $html = '<section class="sidebar-box" aria-labelledby="sidebarPpTitle"><ul class="nav nav-list motions">';
    $html .= '<li class="nav-header" id="sidebarPpTitle">' . Yii::t('con', 'sidebar_procedure') . '</li>';

    $htmlSmall = '<section><h2>' . Yii::t('con', 'sidebar_procedure') . '</h2><ul>';

    if ($settings->proposalProcedurePage) {
        $name = '<span class="glyphicon glyphicon-file" aria-hidden="true"></span>' . Yii::t('con', 'proposed_procedure');
        $url = UrlHelper::createUrl('consultation/proposed-procedure');
        $html .= '<li>' . Html::a($name, $url, ['id' => 'proposedProcedureLink']) . "</li>\n";
        $htmlSmall .= '<li>' . Html::a(Yii::t('con', 'proposed_procedure'), $url) . '</li>';
    }
    if (count($closedVotings) > 0) {
        $name = '<span class="glyphicon glyphicon-stats" aria-hidden="true"></span>' . Yii::t('con', 'voting_results');
        $url = UrlHelper::createUrl('consultation/voting-results');
        $html .= '<li>' . Html::a($name, $url, ['id' => 'votingResultsLink']) . "</li>\n";
        $htmlSmall .= '<li>' . Html::a(Yii::t('con', 'voting_results'), $url) . '</li>';
    }
    if ($settings->startLayoutResolutions === \app\models\settings\Consultation::START_LAYOUT_RESOLUTIONS_SEPARATE) {
        $name = '<span class="glyphicon glyphicon-file" aria-hidden="true"></span>' . Yii::t('con', 'resolutions');
        $url = UrlHelper::createUrl('consultation/resolutions');
        $html .= '<li>' . Html::a($name, $url, ['id' => 'sidebarResolutions']) . "</li>\n";
        $htmlSmall .= '<li>' . Html::a(Yii::t('con', 'resolutions'), $url) . '</li>';
    }
    if ($settings->startLayoutResolutions === \app\models\settings\Consultation::START_LAYOUT_RESOLUTIONS_DEFAULT) {
        $name = '<span class="glyphicon glyphicon-file" aria-hidden="true"></span>' . Yii::t('con', 'All Motions');
        $url = UrlHelper::createUrl('consultation/motions');
        $html .= '<li>' . Html::a($name, $url, ['id' => 'sidebarMotions']) . "</li>\n";
        $htmlSmall .= '<li>' . Html::a(Yii::t('con', 'All Motions'), $url) . '</li>';
    }
    $html .= "</ul></section>";
    $htmlSmall .= '</ul></section>';

    $layout->menusHtml[] = $html;
    $layout->menusHtmlSmall[] = $htmlSmall;
}

if ($hasMotions && $settings->sidebarNewMotions) {
    $html = '<section class="sidebar-box" aria-labelledby="sidebarNewMotionsTitle"><ul class="nav nav-list motions">';
    $html .= '<li class="nav-header" id="sidebarNewMotionsTitle">' . Yii::t('con', 'new_motions') . '</li>';
    if (count($newestMotions) === 0) {
        $html .= '<li><i>' . Yii::t('con', 'sb_motions_none') . '</i></li>';
    } else {
        foreach ($newestMotions as $motion) {
            $motionLink = UrlHelper::createMotionUrl($motion);
            $name       = '<span class="glyphicon glyphicon-file" aria-hidden="true"></span>' .
                HTMLTools::encodeAddShy($motion->title ?: '-');
            $html       .= '<li class="motionTitle">' . Html::a($name, $motionLink) . "</li>\n";
        }
    }
    $html                .= "</ul></section>";
    $layout->menusHtml[] = $html;
}

if ($hasAmendments && $settings->sidebarNewMotions) {
    $html = '<section class="sidebar-box" aria-labelledby="sidebarNewAmendmentsTitle"><ul class="nav nav-list amendments">';
    $html .= '<li class="nav-header" id="sidebarNewAmendmentsTitle">' . Yii::t('con', 'new_amendments') . '</li>';
    if (count($newestAmendments) == 0) {
        $html .= '<li><i>' . Yii::t('con', 'sb_amends_none') . '</i></li>';
    } else {
        foreach ($newestAmendments as $amendment) {
            $title = explode(' ', Html::encode($amendment->getShortTitle()));
            if (count($title) > 1) {
                $title[0] = '<strong>' . Html::encode($title[0]) . '</strong>';
            }
            $title     = implode(' ', $title);
            $amendLink = UrlHelper::createAmendmentUrl($amendment);
            $linkTitle = '<span class="glyphicon glyphicon-flash" aria-hidden="true"></span>' . $title;
            $html      .= '<li>' . Html::a($linkTitle, $amendLink, ['class' => 'amendment' . $amendment->id]) . '</li>';
        }
    }
    $html                .= "</ul></section>";
    $layout->menusHtml[] = $html;
}


if ($hasComments) {
    $html = '<section class="sidebar-box" aria-labelledby="sidebarNewCommentsTitle"><ul class="nav nav-list comments">' .
        '<li class="nav-header" id="sidebarNewCommentsTitle">' . Yii::t('con', 'new_comments') . '</li>';
    if (count($newestComments) == 0) {
        $html .= '<li><i>' . Yii::t('con', 'sb_comm_none') . '</i></li>';
    } else {
        foreach ($newestComments as $comment) {
            $html .= '<li><a href="' . Html::encode($comment->getLink()) . '">';
            $html .= '<span class="glyphicon glyphicon-comment" aria-hidden="true"></span>';
            $html .= '<strong>' . Html::encode($comment->name) . '</strong>, ';
            $html .= Tools::formatMysqlDateTime($comment->dateCreation);
            if (is_a($comment, \app\models\db\MotionComment::class)) {
                $html .= '<div>' . Yii::t('con', 'sb_comm_to') . ' ' .
                    Html::encode($comment->getIMotion()->getFormattedTitlePrefix(\app\models\layoutHooks\Layout::CONTEXT_MOTION)) . '</div>';
            } elseif (is_a($comment, \app\models\db\AmendmentComment::class)) {
                $html .= '<div>' . Yii::t('con', 'sb_comm_to') . ' ' .
                    Html::encode($comment->getIMotion()->getFormattedTitlePrefix(\app\models\layoutHooks\Layout::CONTEXT_MOTION)) . '</div>';
            }
            $html .= '</a></li>';
        }
    }
    $html                .= '</ul></section>';
    $layout->menusHtml[] = $html;
}

if ($hasPDF) {
    $cache = \app\views\consultation\LayoutHelper::getSidebarPdfCache($consultation);
    list($menusStd, $menusSmall) = $cache->getCached(function () use ($consultation, $hasAmendments) {
        $menusStd = [];
        $menusSmall = [];

        $opts = ['class' => 'motionPdfCompilation'];
        $html = '<section class="sidebar-box" aria-labelledby="sidebarPdfsTitle"><ul class="nav nav-list"><li class="nav-header" id="sidebarPdfsTitle">PDFs</li>';

        $hasResolutions = false;
        foreach ($consultation->motions as $motion) {
            if ($motion->isResolution() && $motion->getMyMotionType()->hasPdfLayout()) {
                $hasResolutions = $motion->motionTypeId;
            }
        }
        if ($hasResolutions) {
            $pdfLink = UrlHelper::createUrl([
                '/motion/pdfcollection',
                'motionTypeId' => $hasResolutions,
                'resolutions' => 1,
                'filename'    => 'resolutions.pdf',
            ]);
            $name    = '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>' . Yii::t('con', 'pdf_resolutions');
            $html    .= '<li>' . HtmlTools::createExternalLink($name, $pdfLink, ['class' => 'resolutionPdfCompilation']) . '</li>';

            $link                     = Html::a(Yii::t('con', 'pdf_resolutions'), $pdfLink, $opts);
            $menusSmall[] = '<li>' . $link . '</li>';
        }

        if (count($consultation->motionTypes) > 1) {
            foreach ($consultation->motionTypes as $motionType) {
                if (count($motionType->getVisibleMotions(false)) === 0) {
                    continue;
                }
                if (!$motionType->hasPdfLayout()) {
                    continue;
                }
                $pdfLink = UrlHelper::createUrl([
                    '/motion/pdfcollection',
                    'motionTypeId' => $motionType->id,
                    'filename'     => $motionType->titlePlural . '.pdf',
                ]);
                $name    = '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>' . Yii::t('con', 'pdf_all_short');
                $name    .= ': ' . Html::encode($motionType->titlePlural);
                $html    .= '<li>' . HtmlTools::createExternalLink($name, $pdfLink, ['class' => 'motionPdfCompilation']) . '</li>';

                $link                     = Html::a(Yii::t('con', 'pdf_motions'), $pdfLink, $opts);
                $menusSmall[] = '<li>' . $link . '</li>';
            }
        } elseif (count($consultation->motionTypes) === 1) {
            $pdfLink = UrlHelper::createUrl([
                '/motion/pdfcollection',
                'motionTypeId' => $consultation->motionTypes[0]->id,
                'filename'     => $consultation->motionTypes[0]->titlePlural . '.pdf',
            ]);
            $name    = '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>' . Yii::t('con', 'pdf_all');
            $html    .= '<li>' . HtmlTools::createExternalLink($name, $pdfLink, ['class' => 'motionPdfCompilation']) . '</li>';

            $link                     = Html::a(Yii::t('con', 'pdf_motions'), $pdfLink, $opts);
            $menusSmall[] = '<li>' . $link . '</li>';
        }

        if ($hasAmendments) {
            $amendPdfLink = UrlHelper::createUrl([
                'amendment/pdfcollection',
                'filename' => Yii::t('con', 'feed_amendments') . '.pdf',
            ]);
            $linkTitle    = '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>';
            $linkTitle    .= Yii::t('con', 'pdf_amendments');
            $html         .= '<li>' . HtmlTools::createExternalLink($linkTitle, $amendPdfLink, ['class' => 'amendmentPdfs']) . '</li>';
            $link         = Html::a(Yii::t('con', 'pdf_amendments_small'), $amendPdfLink, ['class' => 'amendmentPdfs']);

            $menusSmall[] = '<li>' . $link . '</li>';
        }

        $html                .= '</ul></section>';
        $menusStd[] = $html;

        return [$menusStd, $menusSmall];
    });

    $layout->menusHtml = array_merge($layout->menusHtml, $menusStd);
    $layout->menusHtmlSmall[] = '<section><h2>PDFs</h2><ul>' . implode('', $menusSmall) . '</ul></section>';
}

if ($consultation->site->getSettings()->showAntragsgruenAd) {
    $layout->postSidebarHtml = \app\models\layoutHooks\Layout::getAntragsgruenAd();
}
