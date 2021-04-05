<?php

namespace app\models\layoutHooks;

use app\components\{Tools, UrlHelper};
use app\controllers\{admin\IndexController, Base, UserController};
use app\models\AdminTodoItem;
use app\models\db\{Amendment, ConsultationMotionType, ConsultationText, ISupporter, Motion, User};
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

class StdHooks extends Hooks
{
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
        $out = '<div class="row logo">';
        $out .= '<a href="' . Html::encode(UrlHelper::homeUrl()) . '" class="homeLinkLogo">';
        $out .= '<span class="sr-only">' . \Yii::t('base', 'home_back') . '</span>';
        $out .= $this->layout->getLogoStr();
        $out .= '</a>';
        $out .= '</div>';

        return $out;
    }

    public function favicons(string $before): string
    {
        /** @var AntragsgruenApp $params */
        $params       = \Yii::$app->params;
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
<meta name="msapplication-TileColor" content="#00a300">
<meta name="msapplication-TileImage" content="' . $faviconBase . '/mstile-150x150.png">
<meta name="theme-color" content="#ffffff">';
    }

    public function beforeContent(string $before): string
    {
        return Layout::breadcrumbs();
    }

    public function breadcrumbs(string $before): string
    {
        $out             = '';
        $showBreadcrumbs = (!$this->consultation || !$this->consultation->site || $this->consultation->site->getSettings()->showBreadcrumbs);
        if (is_array($this->layout->breadcrumbs) && $showBreadcrumbs) {
            $out .= '<nav aria-label="' . \Yii::t('base', 'aria_breadcrumb') . '"><ol class="breadcrumb">';
            foreach ($this->layout->breadcrumbs as $link => $name) {
                if ($link === '' || is_numeric($link)) {
                    $out .= '<li>' . Html::encode($name) . '</li>';
                } else {
                    if ($link === UrlHelper::homeUrl()) {
                        // We have enough links to the home page already, esp. the logo just a few pixels away. This would be confusing for screenreaders.
                        $out .= '<li><span class="pseudoLink" data-href="' . Html::encode($link) . '">' . Html::encode($name) . '</a></li>';
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
        return $before . Layout::footerLine();
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
        $str = $before . $this->layout->preSidebarHtml;
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
                $aename = $amend->titlePrefix;
                if ($aename === '') {
                    $aename = $amend->id;
                }
                $amendLink     = UrlHelper::createAmendmentUrl($amend);
                $amendStatuses = Amendment::getStatusNames();
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
            $name = Html::encode($supporter->name);
            $orga = Html::encode(trim($supporter->organization, " \t\n\r\0\x0B"));
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
                    $orga .= Tools::formatMysqlDate($supporter->resolutionDate, null, false);
                    $orga .= ')</small>';
                }
                return $orga;
            }
        } else {
            $name = $supporter->name;
            $orga = trim($supporter->organization, " \t\n\r\0\x0B");
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
                    $orga .= Tools::formatMysqlDate($supporter->resolutionDate, null, false) . ')';
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

    public function getStdNavbarHeader(string $before): string
    {
        /** @var Base $controller */
        $controller = \Yii::$app->controller;

        $out = '<ul class="nav navbar-nav">';

        if (!defined('INSTALLING_MODE') || INSTALLING_MODE !== true) {
            $consultation       = $controller->consultation;
            $privilegeScreening = User::havePrivilege($consultation, User::PRIVILEGE_SCREENING);
            //$privilegeAny       = User::havePrivilege($consultation, User::PRIVILEGE_ANY);
            $privilegeProposal = User::havePrivilege($consultation, User::PRIVILEGE_CHANGE_PROPOSALS);
            $privilegeSpeech = User::havePrivilege($consultation, User::PRIVILEGE_SPEECH_QUEUES);

            if ($controller->consultation) {
                if (User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
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

                $pages = ConsultationText::getMenuEntries($controller->site, $controller->consultation);
                foreach ($pages as $page) {
                    $options = ['class' => 'page' . $page->id, 'aria-label' => $page->title];
                    $out     .= '<li>' . Html::a($page->title, $page->getUrl(), $options) . '</li>';
                }
            }

            if (!User::getCurrentUser()) {
                if (get_class($controller) === UserController::class) {
                    $backUrl = UrlHelper::createUrl('/consultation/index');
                } else {
                    $backUrl = \Yii::$app->request->url;
                }
                $loginUrl   = UrlHelper::createUrl(['/user/login', 'backUrl' => $backUrl]);
                $loginTitle = \Yii::t('base', 'menu_login');
                $out        .= '<li>' . Html::a($loginTitle, $loginUrl, ['id' => 'loginLink', 'rel' => 'nofollow', 'aria-label' => $loginTitle]) .
                               '</li>';
            }
            if (User::getCurrentUser()) {
                $link = Html::a(
                    \Yii::t('base', 'menu_account'),
                    UrlHelper::createUrl('/user/myaccount'),
                    ['id' => 'myAccountLink']
                );
                $out  .= '<li>' . $link . '</li>';

                if (get_class($controller) === UserController::class) {
                    $backUrl = UrlHelper::createUrl('/consultation/index');
                } else {
                    $backUrl = \Yii::$app->request->url;
                }
                $logoutUrl   = UrlHelper::createUrl(['/user/logout', 'backUrl' => $backUrl]);
                $logoutTitle = \Yii::t('base', 'menu_logout');
                $out         .= '<li>' . Html::a($logoutTitle, $logoutUrl, ['id' => 'logoutLink', 'aria-label' => $logoutTitle]) . '</li>';
            }
            if ($privilegeScreening || $privilegeProposal) {
                $adminUrl   = UrlHelper::createUrl('/admin/motion-list/index');
                $adminTitle = \Yii::t('base', 'menu_motion_list');
                $out        .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'motionListLink', 'aria-label' => $adminTitle]) . '</li>';
            }
            if ($privilegeSpeech && $consultation->getSettings()->hasSpeechLists) {
                $adminUrl = UrlHelper::createUrl(['consultation/admin-speech']);
                $adminTitle = \Yii::t('base', 'menu_speech_lists');
                $out        .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'speechAdminLink', 'aria-label' => $adminTitle]) . '</li>';
            }
            if ($privilegeScreening) {
                $todo = AdminTodoItem::getConsultationTodos($controller->consultation);
                if (count($todo) > 0) {
                    $adminUrl   = UrlHelper::createUrl('/admin/index/todo');
                    $adminTitle = \Yii::t('base', 'menu_todo') . ' (' . count($todo) . ')';
                    $out        .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'adminTodo', 'aria-label' => $adminTitle]) . '</li>';
                }
            }
            if (User::havePrivilege($consultation, IndexController::$REQUIRED_PRIVILEGES)) {
                $adminUrl   = UrlHelper::createUrl('/admin/index');
                $adminTitle = \Yii::t('base', 'menu_admin');
                $out        .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'adminLink', 'aria-label' => $adminTitle]) . '</li>';
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
                <span class="glyphicon glyphicon-chevron-right"></span> Infos
                </a>
            </div>
        </div>
    </section>';
    }

    public function footerLine(string $before): string
    {
        $out = '<footer class="footer" aria-label="' . \Yii::t('base', 'aria_footer') . '">';

        if (!defined('INSTALLING_MODE') || INSTALLING_MODE !== true) {
            $legalLink   = UrlHelper::createUrl(['/pages/show-page', 'pageSlug' => 'legal']);
            $privacyLink = UrlHelper::createUrl(['/pages/show-page', 'pageSlug' => 'privacy']);

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
     * @param string $before
     * @param ConsultationMotionType[] $motionTypes
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSidebarCreateMotionButton($before, $motionTypes)
    {
        $html      = '<div class="createMotionHolder1"><div class="createMotionHolder2">';
        $htmlSmall = '';

        foreach ($motionTypes as $motionType) {
            $link        = $motionType->getCreateLink();
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

    public function getConsultationPreWelcome(string $before): string
    {
        $str = '';
        $highlightedDeadlines = [];
        foreach ($this->consultation->motionTypes as $motionType) {
            $deadline = $motionType->getUpcomingDeadline(ConsultationMotionType::DEADLINE_MOTIONS);
            if ($motionType->sidebarCreateButton && $deadline && !in_array($deadline, $highlightedDeadlines)) {
                $highlightedDeadlines[] = $deadline;
            }
        }
        if (count($highlightedDeadlines) === 1) {
            $str = '<p class="deadlineCircle">' . \Yii::t('con', 'deadline_circle') . ': ';
            $str .= Tools::formatMysqlDateTime($highlightedDeadlines[0]) . "</p>\n";
        }

        return $str;
    }
}
