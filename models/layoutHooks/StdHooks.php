<?php

declare(strict_types=1);

namespace app\models\layoutHooks;

use app\models\settings\{PrivilegeQueryContext, Privileges, AntragsgruenApp};
use app\components\{HTMLTools, RequestContext, Tools, UrlHelper};
use app\controllers\{admin\IndexController, admin\MotionListController, UserController};
use app\models\AdminTodoItem;
use app\models\db\{Amendment, Consultation, ConsultationMotionType, ConsultationText, ISupporter, Motion, User};
use yii\helpers\Html;

class StdHooks extends Hooks
{
    public function beforePage(string $before): string
    {
        return '';
    }

    public function beginPage(string $before): string
    {
        $out = '<header id="mainmenu">';
        $out .= '<nav class="navbar" aria-label="' . \Yii::t('base', 'aria_mainmenu') . '">
        <div class="navbar-inner">';
        $out .= Layout::getStdNavbarHeader();
        $out .= '</div>
        </nav>';

        $out .= '</header>';

        return $out;
    }

    public function logoRow(string $before): string
    {
        $out = '<div class="logoRow">';
        $out .= '<a href="' . Html::encode(UrlHelper::homeUrl()) . '" class="homeLinkLogo">';
        $out .= '<span class="sr-only">' . \Yii::t('base', 'home_back') . '</span>';
        $out .= $this->layout->getLogoStr();
        $out .= '</a>';
        $out .= '</div>';

        return $out;
    }

    public function favicons(string $before): string
    {
        $params       = AntragsgruenApp::getInstance();
        $resourceBase = Html::encode($params->resourceBase);
        if (defined('YII_FROM_ROOTDIR') && YII_FROM_ROOTDIR === true) {
            $resourceBase .= 'web/';
        }
        $faviconBase = $resourceBase . 'favicons';

        return '<link rel="apple-touch-icon" sizes="180x180" href="' . $faviconBase . '/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="' . $faviconBase . '/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="' . $faviconBase . '/favicon-16x16.png">
<link rel="manifest" href="' . $faviconBase . '/site.webmanifest">
<link rel="mask-icon" href="' . $faviconBase . '/safari-pinned-tab.svg" color="#3bb030">
<meta name="theme-color" content="#ffffff">';
    }

    public function beforeContent(string $before): string
    {
        $out = '';
        if ($this->consultation) {
            $warnings = array_merge(
                Layout::getSitewidePublicWarnings($this->consultation->site),
                Layout::getConsultationwidePublicWarnings($this->consultation)
            );
            if (count($warnings) > 0) {
                $out .= '<div class="alert alert-danger consultationwideWarning">';
                $out .= '<p>' . implode('</p><p>', $warnings) . '</p>';
                $out .= '</div>';
            }
        }

        $out .= Layout::breadcrumbs();

        return $out;
    }

    public function breadcrumbs(string $before): string
    {
        $out             = '';
        $showBreadcrumbs = (!$this->consultation || !$this->consultation->site || $this->consultation->site->getSettings()->showBreadcrumbs);
        if (count($this->layout->breadcrumbs) > 0 && $showBreadcrumbs) {
            $out .= '<nav aria-label="' . \Yii::t('base', 'aria_breadcrumb') . '"><ol class="breadcrumb">';
            foreach ($this->layout->breadcrumbs as $link => $name) {
                if ($link === '' || is_numeric($link)) {
                    $out .= '<li>' . Html::encode($name) . '</li>';
                } else {
                    if ($link === UrlHelper::homeUrl()) {
                        // We have enough links to the home page already, esp. the logo just a few pixels away. This would be confusing for screenreaders.
                        $out .= '<li><span class="pseudoLink" data-href="' . Html::encode($link) . '">' . Html::encode($name) . '</span></li>';
                    } else {
                        $label = str_replace('%TITLE%', $name, \Yii::t('base', 'aria_bc_back'));
                        $out   .= '<li>' . Html::a(Html::encode($name), $link, ['aria-label' => $label]) . '</li>';
                    }
                }
            }
            $out .= '</ol></nav>';
        }

        return $out;
    }

    public function endPage(string $before): string
    {
        return Layout::footerLine();
    }

    public function getSearchForm(string $before): string
    {
        $html = Html::beginForm(UrlHelper::createUrl('consultation/search'), 'post', [
            'class'           => 'form-search',
            'aria-labelledby' => 'sidebarSearchTitle',
        ]);
        $html .= '<section class="nav-list" id="sidebarSearch" aria-labelledby="sidebarSearchTitle"><div class="nav-header" id="sidebarSearchTitle">' . \Yii::t('con', 'sb_search') . '</div>
    <div class="searchContent">
    <div class="input-group">
      <label for="searchQuery" class="sr-only">' . \Yii::t('con', 'sb_search_query') . '</label>
      <input type="text" class="form-control query" name="query" id="searchQuery"
        placeholder="' . Html::encode(\Yii::t('con', 'sb_search_query')) . '" required>
      <span class="input-group-btn">
        <button class="btn btn-default" type="submit" title="' . Html::encode(\Yii::t('con', 'sb_search_do')) . '">
            <span class="glyphicon glyphicon-search" aria-hidden="true"></span> ' . \Yii::t('con', 'sb_search_do') . '
        </button>
      </span>
    </div>
    </div>
</section>';
        $html .= Html::endForm();

        return $html;
    }

    public function renderSidebar(string $before): string
    {
        $str = $this->layout->preSidebarHtml;
        if (count($this->layout->menusHtml) > 0) {
            $str .= '<div class="well hidden-xs">';
            $str .= implode('', $this->layout->menusHtml);
            $str .= '</div>';
        }
        $str .= $this->layout->postSidebarHtml;

        return $str;
    }

    public function getMotionFormattedAmendmentList(string $before, Motion $motion): string
    {
        $consultation = $motion->getMyConsultation();
        $amendments = $motion->getVisibleAmendments();
        // Global alternatives first, then sorted by titlePrefix
        usort($amendments, function (Amendment $amend1, Amendment $amend2) {
            if ($amend1->globalAlternative && !$amend2->globalAlternative) {
                return -1;
            }
            if (!$amend1->globalAlternative && $amend2->globalAlternative) {
                return 1;
            }

            return strnatcasecmp($amend1->titlePrefix, $amend2->titlePrefix);
        });

        $before = '';
        if (count($amendments) > 0) {
            $before .= '<ul class="amendments">';
            foreach ($amendments as $amend) {
                $before .= '<li>';
                if ($amend->globalAlternative) {
                    $before .= '<strong>' . \Yii::t('amend', 'global_alternative') . ':</strong> ';
                }
                $aename = $amend->getFormattedTitlePrefix(Layout::CONTEXT_MOTION_LIST);
                if ($aename === '') {
                    $aename = $amend->id;
                }
                $amendLink     = UrlHelper::createAmendmentUrl($amend);
                $amendStatuses = $consultation->getStatuses()->getStatusNames();
                $before        .= Html::a(Html::encode($aename), $amendLink, ['class' => 'amendment' . $amend->id]);
                $before        .= ' (' . Html::encode($amend->getInitiatorsStr() . ', ' . $amendStatuses[$amend->status]) . ')';
                $before        .= '</li>';
            }
            $before .= '</ul>';
        } else {
            $before .= '<em>' . \Yii::t('motion', 'amends_none') . '</em>';
        }

        return $before;
    }

    public function getSupporterNameWithOrga(string $before, ISupporter $supporter): string
    {
        if ($supporter->personType === ISupporter::PERSON_NATURAL || $supporter->personType === null) {
            $name = $supporter->name;
            if ($name == '' && $supporter->getMyUser()) {
                $name = $supporter->getMyUser()->name;
            }
            if ($supporter->organization != '') {
                $name .= ' (' . trim($supporter->organization, " \t\n\r\0\x0B()") . ')';
            }
            return $name;
        } else {
            return trim($supporter->organization, " \t\n\r\0\x0B()");
        }
    }

    public function getSupporterNameWithResolutionDate(string $before, ISupporter $supporter, bool $html): string
    {
        if ($html) {
            $name = Html::encode($supporter->name ?: '');
            $orga = Html::encode(trim($supporter->organization ?: '', " \t\n\r\0\x0B"));
            if ($name == '' && $supporter->getMyUser()) {
                $name = Html::encode($supporter->getMyUser()->name);
            }
            if ($supporter->personType === ISupporter::PERSON_NATURAL || $supporter->personType === null) {
                if ($orga != '') {
                    $name .= ' <small style="font-weight: normal;">';
                    $name .= '(' . $orga . ')';
                    $name .= '</small>';
                }
                return $name;
            } else {
                if ($supporter->resolutionDate > 0) {
                    $orga .= ' <small style="font-weight: normal;">(';
                    $orga .= \Yii::t('motion', 'resolution_on') . ': ';
                    $orga .= Tools::formatMysqlDate($supporter->resolutionDate, false);
                    $orga .= ')</small>';
                }
                return $orga;
            }
        } else {
            $name = $supporter->name;
            $orga = trim($supporter->organization ?: '', " \t\n\r\0\x0B");
            if ($name == '' && $supporter->getMyUser()) {
                $name = $supporter->getMyUser()->name;
            }
            if ($supporter->personType === ISupporter::PERSON_NATURAL || $supporter->personType === null) {
                if ($orga !== '') {
                    $name .= ' (' . $orga . ')';
                }
                return $name;
            } else {
                if ($supporter->resolutionDate > 0) {
                    $orga .= ' (' . \Yii::t('motion', 'resolution_on') . ': ';
                    $orga .= Tools::formatMysqlDate($supporter->resolutionDate, false) . ')';
                }
                return $orga;
            }
        }
    }

    public function getAmendmentBookmarkName(string $before, Amendment $amendment): string
    {
        if (!$this->consultation->getSettings()->amendmentBookmarksWithNames) {
            return '';
        }
        if (count($amendment->getInitiators()) === 0) {
            return '';
        }
        $initiator = $amendment->getInitiators()[0];
        if ($initiator->personType === ISupporter::PERSON_ORGANIZATION) {
            return ' <small>' . Html::encode($initiator->organization) . '</small>';
        } else {
            return ' <small>' . Html::encode($initiator->name) . '</small>';
        }
    }

    protected function addMotionListNavbarEntry(Consultation $consultation): string
    {
        if (!MotionListController::haveAccessToList($consultation)) {
            return '';
        }

        $adminUrl   = UrlHelper::createUrl('/admin/motion-list/index');
        $adminTitle = \Yii::t('base', 'menu_motion_list');
        return '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'motionListLink', 'aria-label' => $adminTitle]) . '</li>';
    }

    public function getStdNavbarHeader(string $before): string
    {
        $out = '<ul class="nav navbar-nav">';

        if (!defined('INSTALLING_MODE') || INSTALLING_MODE !== true) {
            $consultation       = Consultation::getCurrent();

            if ($consultation) {
                if (User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
                    $icon = '<span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>';
                    $icon .= '<span class="sr-only">' . \Yii::t('pages', 'menu_add_btn') . '</span>';
                    $url  = UrlHelper::createUrl('/pages/list-pages');
                    $out  .= '<li class="addPage">' .
                             Html::a($icon, $url, ['title' => \Yii::t('pages', 'menu_add_btn')]) . '</li>';
                }

                $homeUrl = UrlHelper::homeUrl();
                $out     .= '<li class="active">' .
                            Html::a(\Yii::t('base', 'Home'), $homeUrl, ['id' => 'homeLink', 'aria-label' => \Yii::t('base', 'home_back')]) .
                            '</li>';

                $pages = ConsultationText::getMenuEntries(Consultation::getCurrent()->site, $consultation);
                foreach ($pages as $page) {
                    $options = ['class' => 'page' . $page->id, 'aria-label' => $page->title];
                    $out     .= '<li>' . Html::a(Html::encode($page->title), $page->getUrl(), $options) . '</li>';
                }
            }

            if ($consultation) {
                $out .= $this->addMotionListNavbarEntry($consultation);
            }

            if (User::havePrivilege($consultation, Privileges::PRIVILEGE_ANY, PrivilegeQueryContext::anyRestriction())) {
                $todo = AdminTodoItem::getConsultationTodoCount($consultation, true);
                $adminUrl = UrlHelper::createUrl('/consultation/todo');
                if ($todo === null) {
                    $asyncLoad = UrlHelper::createUrl('/consultation/todo-count');
                    $adminTitle = \Yii::t('base', 'menu_todo') . ' (###COUNT###)';
                    $out .= '<li data-url="' . Html::encode($asyncLoad) . '" class="hidden" id="adminTodoLoader">';
                    $out .= Html::a($adminTitle, $adminUrl, ['id' => 'adminTodo', 'aria-label' => $adminTitle]);
                    $out .= '</li>';
                } elseif ($todo > 0) {
                    $adminTitle = \Yii::t('base', 'menu_todo') . ' (' . $todo . ')';
                    $out        .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'adminTodo', 'aria-label' => $adminTitle]) . '</li>';
                }
            }

            if ($consultation && $consultation->getSettings()->documentPage) {
                $adminUrl = UrlHelper::createUrl(['/pages/documents']);
                $adminTitle = \Yii::t('base', 'menu_documents');
                $out        .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'documentsLink', 'aria-label' => $adminTitle]) . '</li>';
            }

            if ($consultation && $consultation->getSettings()->hasSpeechLists && $consultation->getSettings()->speechPage) {
                $adminUrl = UrlHelper::createUrl(['/consultation/speech']);
                $adminTitle = \Yii::t('base', 'menu_speech_list');
                $out        .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'speechLink', 'aria-label' => $adminTitle]) . '</li>';
            }

            if ($consultation && $consultation->getSettings()->votingPage) {
                $adminUrl = UrlHelper::createUrl(['/consultation/votings']);
                $adminTitle = \Yii::t('base', 'menu_votings');
                $out        .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'votingsLink', 'aria-label' => $adminTitle]) . '</li>';
            }

            if (User::haveOneOfPrivileges($consultation, IndexController::REQUIRED_PRIVILEGES, null)) {
                $adminUrl   = UrlHelper::createUrl('/admin/index');
                $adminTitle = \Yii::t('base', 'menu_admin');
                $out        .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'adminLink', 'aria-label' => $adminTitle]) . '</li>';
            }

            if (get_class(RequestContext::getController()) === UserController::class) {
                $backUrl = UrlHelper::createUrl('/consultation/index');
            } else {
                $backUrl = RequestContext::getWebRequest()->url;
            }
            if (User::getCurrentUser()) {
                $link = Html::a(
                    \Yii::t('base', 'menu_account'),
                    UrlHelper::createUrl('/user/myaccount'),
                    ['id' => 'myAccountLink']
                );
                $out  .= '<li>' . $link . '</li>';

                $logoutUrl   = UrlHelper::createUrl(['/user/logout', 'backUrl' => $backUrl]);
                $logoutTitle = \Yii::t('base', 'menu_logout');
                $out         .= '<li>' . Html::a($logoutTitle, $logoutUrl, ['id' => 'logoutLink', 'aria-label' => $logoutTitle]) . '</li>';
            } else {
                $loginUrl   = UrlHelper::createUrl(['/user/login', 'backUrl' => $backUrl]);
                $loginTitle = \Yii::t('base', 'menu_login');
                $out        .= '<li>' . Html::a($loginTitle, $loginUrl, ['id' => 'loginLink', 'rel' => 'nofollow', 'aria-label' => $loginTitle]) .
                               '</li>';
            }
        }
        $out .= '</ul>';

        return $out;
    }

    public function getAntragsgruenAd(string $before): string
    {
        if (\Yii::$app->language === 'de') {
            $url = 'https://antragsgruen.de/';
        } else {
            $url = 'https://motion.tools/';
        }

        return '<section class="antragsgruenAd well" aria-labelledby="sidebarAntragsgruenTitle">
        <div class="nav-header" id="sidebarAntragsgruenTitle">' . \Yii::t('con', 'aad_title') . '</div>
        <div class="content">
            ' . \Yii::t('con', 'aad_text') . '
            <div>
                <a href="' . $url . '" title="' . \Yii::t('con', 'aad_btn') . '" class="btn btn-primary">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> Infos
                </a>
            </div>
        </div>
    </section>';
    }

    public function footerLine(string $before): string
    {
        $out = '<footer class="footer" aria-label="' . \Yii::t('base', 'aria_footer') . '">';

        if (!defined('INSTALLING_MODE') || INSTALLING_MODE !== true) {
            if ($this->consultation) {
                $legalLink   = UrlHelper::createUrl(['/pages/show-page', 'pageSlug' => 'legal', 'consultationPath' => $this->consultation->urlPath]);
                $privacyLink = UrlHelper::createUrl(['/pages/show-page', 'pageSlug' => 'privacy', 'consultationPath' => $this->consultation->urlPath]);
            } else {
                $legalLink   = UrlHelper::createUrl(['/pages/show-page', 'pageSlug' => 'legal']);
                $privacyLink = UrlHelper::createUrl(['/pages/show-page', 'pageSlug' => 'privacy']);
            }

            $out .= '<a href="' . Html::encode($legalLink) . '" class="legal" id="legalLink">' .
                    \Yii::t('base', 'imprint') . '</a>
            <a href="' . Html::encode($privacyLink) . '" class="privacy" id="privacyLink">' .
                    \Yii::t('base', 'privacy_statement') . '</a>';
        }

        $out .= '<span class="version">';
        if (\Yii::$app->language === 'de') {
            $ariaVersion = str_replace('%VERSION%', ANTRAGSGRUEN_VERSION, \Yii::t('base', 'aria_version_hint'));
            $out         .= '<a href="https://antragsgruen.de/" aria-label="' . \Yii::t('base', 'aria_antragsgruen') . '">Antragsgrün</a>, Version ' .
                            Html::a(Html::encode(ANTRAGSGRUEN_VERSION), ANTRAGSGRUEN_HISTORY_URL, ['aria-label' => $ariaVersion]);
        } else {
            $ariaVersion = str_replace('%VERSION%', ANTRAGSGRUEN_VERSION, \Yii::t('base', 'aria_version_hint'));
            $out         .= '<a href="https://motion.tools/" aria-label="' . \Yii::t('base', 'aria_antragsgruen') . '">Antragsgrün</a>, Version ' .
                            Html::a(Html::encode(ANTRAGSGRUEN_VERSION), ANTRAGSGRUEN_HISTORY_URL, ['aria-label' => $ariaVersion]);
        }
        $out .= '</span>';

        $out .= '</footer>';

        return $out;
    }

    /**
     * @param ConsultationMotionType[] $motionTypes
     */
    public function setSidebarCreateMotionButton(string $before, array $motionTypes): string
    {
        $html      = '<div class="createMotionHolder1"><div class="createMotionHolder2">';
        $htmlSmall = '';

        foreach ($motionTypes as $motionType) {
            $link        = $motionType->getCreateLink(false, true);
            $description = $motionType->createTitle;

            $html      .= '<a class="createMotion createMotion' . $motionType->id . '" ' .
                          'href="' . Html::encode($link) . '" title="' . Html::encode($description) . '" rel="nofollow">' .
                          '<span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>' . Html::encode($description) .
                          '</a>';
            $htmlSmall .=
                '<a class="navbar-brand" href="' . Html::encode($link) . '" rel="nofollow">' .
                '<span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>' . Html::encode($description) . '</a>';
        }

        $html                               .= '</div></div>';
        $this->layout->menusHtml[]          = $html;
        $this->layout->menusSmallAttachment = $htmlSmall;

        return '';
    }

    public function getConsultationwidePublicWarnings(array $before, Consultation $consultation): array
    {
        if ($consultation->getSettings()->maintenanceMode && User::getCurrentUser() &&
            User::getCurrentUser()->hasPrivilege($consultation, Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null)) {
            $url = UrlHelper::createUrl('/admin/index/consultation');
            $before[] = str_replace('%URL%', $url, \Yii::t('base', 'head_maintenance_adm'));
        }

        return $before;
    }

    /**
     * Show upcoming deadlines according to the following cases:
     * - If only one date is upcoming, just show the date
     * - If multiple dates are upcoming, show the first one big, and the others in the tooltip.
     *   - If the first upcoming date is only for one specific motion type, show its name
     *   - If the first upcoming date is for multiple motion types, don't show the name, refer to tooltip
     */
    public function getConsultationPreWelcome(string $before): string
    {
        if (!$this->consultation->getSettings()->homepageDeadlineCircle) {
            return '';
        }

        /** @var array<string, array{date: string, titles: string[]}> $deadlines */
        $deadlines = [];
        foreach ($this->consultation->motionTypes as $motionType) {
            $deadline = $motionType->getUpcomingDeadline(ConsultationMotionType::DEADLINE_MOTIONS);
            if ($deadline) {
                if (!isset($deadlines[$deadline])) {
                    $deadlines[$deadline] = ['date' => $deadline, 'titles' => []];
                }
                $deadlines[$deadline]['titles'][] = $motionType->titlePlural;
            }

            $deadline = $motionType->getUpcomingDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS);
            if ($deadline) {
                if (!isset($deadlines[$deadline])) {
                    $deadlines[$deadline] = ['date' => $deadline, 'titles' => []];
                }
                $deadlines[$deadline]['titles'][] = \Yii::t('con', 'deadline_amendments');
            }
        }

        if (count($deadlines) === 0) {
            $str = '';
        } elseif (count($deadlines) === 1) {
            $str = '<div class="deadlineCircle single">';
            $str .= '<div class="top">'. \Yii::t('con', 'deadline_circle') . ':</div>';
            $str .= '<div class="date">' . str_replace(" ", "<br>", Tools::formatMysqlDateTime(array_values($deadlines)[0]['date'])) . "</div>";
            $str .= '</div>';
        } else {
            $this->layout->addTooltopOnloadJs();

            $deadlines = array_values($deadlines);
            usort($deadlines, fn ($a, $b) => $a['date'] <=> $b['date']);

            $deadlinesStrs = array_map(function ($deadline) {
                return Tools::formatMysqlDateTime($deadline['date'], false) . ': ' . implode(", ", $deadline['titles']);
            }, $deadlines);
            $icon = HTMLTools::getTooltipIcon(\Yii::t('con', 'deadline_all') . ':<br>' . implode("<br>", $deadlinesStrs), 'bottom', true);
            $str = '<div class="deadlineCircle multiple">';
            $str .='<div class="top">' . \Yii::t('con', 'deadline_upcoming') . ' <span class="tooltipHolder">' . $icon . '</span></div>';

            if (count($deadlines[0]['titles']) === 1) {
                $str .= '<div class="name singleline"><p>' . Html::encode($deadlines[0]['titles'][0]) . '</p></div>';
                $str .= '<div class="bottom">';
                $str .= str_replace(", ", "<br>", Tools::formatMysqlDateTime($deadlines[0]['date']));
                $str .= '</div>';
            } else {
                $str .= '<div class="name nobottom"><p>' . str_replace(" ", "<br>", Tools::formatMysqlDateTime($deadlines[0]['date'])) . '</p></div>';
            }
            $str .= '</div>';
        }

        return $str;
    }
}
