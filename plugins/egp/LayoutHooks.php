<?php

namespace app\plugins\egp;

use app\components\UrlHelper;
use app\controllers\{admin\IndexController, Base, UserController};
use app\models\AdminTodoItem;
use app\models\db\{Amendment, ConsultationText, Motion, User};
use app\models\layoutHooks\{Hooks, Layout};
use yii\helpers\Html;

class LayoutHooks extends Hooks
{
    public function beginPage(string $before): string
    {
        return '';
    }

    public function renderSidebar(string $before): string
    {
        $str = $this->layout->preSidebarHtml;
        if (count($this->layout->menusHtml) > 0) {
            $str .= '<div class="hidden-xs">';
            $str .= implode('', $this->layout->menusHtml);
            $str .= '</div>';
        }
        $str .= $this->layout->postSidebarHtml;

        return $str;
    }

    public function logoRow(string $before): string
    {
        return '';
    }

    public function beforeContent(string $before): string
    {
        $out = '<section class="navwrap">' .
               '<nav role="navigation" class="pos" id="mainmenu">' .
               '<img src="/img/logo.svg" alt="Logo of the European Green Party" class="logo">' .
               '<h6 class="sr-only">' .
               \Yii::t('base', 'menu_main') . ':</h6>' .
               '<div class="navigation nav-fallback clearfix">';
        $out .= Layout::getStdNavbarHeader();
        $out .= '</div></nav>';
        $out .= Layout::breadcrumbs();
        $out .= '</section>';

        if ($this->consultation) {
            $warnings = Layout::getSitewidePublicWarnings($this->consultation->site);
            if (count($warnings) > 0) {
                $out .= '<div class="alert alert-danger">';
                $out .= '<p>' . implode('</p><p>', $warnings) . '</p>';
                $out .= '</div>';
            }
        }

        return $out;
    }

    public function getSearchForm(string $before): string
    {
        $html = Html::beginForm(UrlHelper::createUrl('consultation/search'), 'post', ['class' => 'form-search']);
        $html .= '<h6 class="invisible">' . \Yii::t('con', 'sb_search_form') . '</h6>';
        $html .= '<label for="search">' . \Yii::t('con', 'sb_search_desc') . '</label>
    <input type="text" class="query" name="query"
        placeholder="' . Html::encode(\Yii::t('con', 'sb_search_query')) . '" required
        title="' . Html::encode(\Yii::t('con', 'sb_search_query')) . '">

    <button type="submit" class="button-submit hidden">
                <span class="fa fa-search"></span> <span class="text">Search</span>
            </button>';
        $html .= Html::endForm();

        return $html;
    }

    public function footerLine(string $before): string
    {
        $out = '<footer class="footer"><div class="container">';

        if (!defined('INSTALLING_MODE') || INSTALLING_MODE !== true) {
            $privacyLink = 'https://europeangreens.eu/content/privacy-policy';

            $out .= '<a href="' . Html::encode($privacyLink) . '" class="privacy" id="privacyLink">' .
                    \Yii::t('base', 'privacy_statement') . '</a>';
        }

        $ariaVersion = str_replace('%VERSION%', ANTRAGSGRUEN_VERSION, \Yii::t('base', 'aria_version_hint'));
        $out         .= '<span class="version">';
        $out         .= '<a href="https://discuss.green/">Discuss.green / Antragsgrün</a>, Version ' .
                        Html::a(Html::encode(ANTRAGSGRUEN_VERSION), ANTRAGSGRUEN_HISTORY_URL, ['aria-label' => $ariaVersion]);
        $out         .= '</span>';

        $out .= '</div></footer>';

        return $out;
    }

    public function getStdNavbarHeader(string $before): string
    {
        /** @var Base $controller */
        $controller = \Yii::$app->controller;

        $out = '<ul class="nav navbar-nav">';

        $consultation       = $controller->consultation;
        $privilegeScreening = User::havePrivilege($consultation, User::PRIVILEGE_SCREENING);
        //$privilegeAny       = User::havePrivilege($consultation, User::PRIVILEGE_ANY);
        $privilegeProposal = User::havePrivilege($consultation, User::PRIVILEGE_CHANGE_PROPOSALS);

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
                $backUrl = \yii::$app->request->url;
            }
            $loginUrl   = UrlHelper::createUrl(['/user/login', 'backUrl' => $backUrl]);
            $loginTitle = \Yii::t('base', 'menu_login');
            $out        .= '<li>' . Html::a($loginTitle, $loginUrl, ['id' => 'loginLink', 'rel' => 'nofollow', 'aria-label' => $loginTitle]) .
                           '</li>';
        }
        if (User::getCurrentUser()) {
            if (get_class($controller) === UserController::class) {
                $backUrl = UrlHelper::createUrl('/consultation/index');
            } else {
                $backUrl = \yii::$app->request->url;
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
        $out .= '</ul>';

        return $out;
    }

    public function getMotionViewData(array $motionData, Motion $motion): array
    {
        foreach ($motionData as $i => $data) {
            if ($motionData[$i]['title'] === \Yii::t('motion', 'initiators_1') || $motionData[$i]['title'] === \Yii::t('motion', 'initiators_x')) {
                $motionData[$i]['title']   = 'Party';
                $initiators                = $motion->getInitiators();
                $motionData[$i]['content'] = '';
                foreach ($initiators as $supp) {
                    $motionData[$i]['content'] = Html::encode($supp->organization);
                }
            }
        }

        return $motionData;
    }

    public function getAmendmentViewData(array $amendmentData, Amendment $amendment): array
    {
        foreach ($amendmentData as $i => $data) {
            if ($amendmentData[$i]['title'] === \Yii::t('motion', 'initiators_1') || $amendmentData[$i]['title'] === \Yii::t('motion', 'initiators_x')) {
                $amendmentData[$i]['title']   = 'Party';
                $initiators                = $amendment->getInitiators();
                $amendmentData[$i]['content'] = '';
                foreach ($initiators as $supp) {
                    $amendmentData[$i]['content'] = Html::encode($supp->organization);
                }
            }
        }

        return $amendmentData;
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
                $before        .= Html::a(Html::encode($aename), $amendLink, ['class' => 'amendment' . $amend->id]);
                $before        .= '</li>';
            }
            $before .= '</ul>';
        } else {
            $before .= '<em>' . \Yii::t('motion', 'amends_none') . '</em>';
        }

        return $before;
    }
}
