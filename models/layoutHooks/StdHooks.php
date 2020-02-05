<?php

namespace app\models\layoutHooks;

use app\components\UrlHelper;
use app\controllers\{admin\IndexController, Base, UserController};
use app\models\AdminTodoItem;
use app\models\db\{ConsultationMotionType, ConsultationText, User};
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

class StdHooks extends Hooks
{
    public function beginPage(string $before): string
    {
        $out = '<header id="mainmenu">';
        $out .= '<div class="navbar">
        <div class="navbar-inner">';
        $out .= Layout::getStdNavbarHeader();
        $out .= '</div>
        </div>';

        $out .= '</header>';

        return $out;
    }

    public function logoRow(string $before): string
    {
        $out = '<div class="row logo">
<a href="' . Html::encode(UrlHelper::homeUrl()) . '" class="homeLinkLogo text-hide">' . \Yii::t('base', 'Home');
        $out .= $this->layout->getLogoStr();
        $out .= '</a></div>';

        return $out;
    }

    public function favicons(string $before): string
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $resourceBase = Html::encode($params->resourceBase);
        if (defined('YII_FROM_ROOTDIR') && YII_FROM_ROOTDIR === true) {
            $resourceBase .= 'web/';
        }
        $faviconBase = $resourceBase . 'favicons';

        $out = '<link rel="apple-touch-icon" sizes="180x180" href="' . $faviconBase . '/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="' . $faviconBase . '/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="' . $faviconBase . '/favicon-16x16.png">
<link rel="manifest" href="' . $faviconBase . '/site.webmanifest">
<link rel="mask-icon" href="' . $faviconBase . '/safari-pinned-tab.svg" color="#3bb030">
<meta name="msapplication-TileColor" content="#00a300">
<meta name="msapplication-TileImage" content="' . $faviconBase . '/mstile-150x150.png">
<meta name="theme-color" content="#ffffff">';

        return $out;
    }

    public function beforeContent(string $before): string
    {
        return Layout::breadcrumbs();
    }

    public function breadcrumbs(string $before): string
    {
        $out = '';
        $showBreadcrumbs = (!$this->consultation || !$this->consultation->site || $this->consultation->site->getSettings()->showBreadcrumbs);
        if (is_array($this->layout->breadcrumbs) && $showBreadcrumbs) {
            $out .= '<ol class="breadcrumb">';
            foreach ($this->layout->breadcrumbs as $link => $name) {
                if ($link === '' || is_numeric($link)) {
                    $out .= '<li>' . Html::encode($name) . '</li>';
                } else {
                    $out .= '<li>' . Html::a(Html::encode($name), $link) . '</li>';
                }
            }
            $out .= '</ol>';
        }

        return $out;
    }

    public function endPage(string $before): string
    {
        return $before . Layout::footerLine();
    }

    public function getSearchForm(string $before): string
    {
        $html = Html::beginForm(UrlHelper::createUrl('consultation/search'), 'post', ['class' => 'form-search']);
        $html .= '<div class="nav-list"><div class="nav-header">' . \Yii::t('con', 'sb_search') . '</div>
    <div style="text-align: center; padding-left: 7px; padding-right: 7px;">
    <div class="input-group">
      <input type="text" class="form-control query" name="query"
        placeholder="' . Html::encode(\Yii::t('con', 'sb_search_query')) . '" required
        title="' . Html::encode(\Yii::t('con', 'sb_search_query')) . '">
      <span class="input-group-btn">
        <button class="btn btn-default" type="submit" title="' . Html::encode(\Yii::t('con', 'sb_search_do')) . '">
            <span class="glyphicon glyphicon-search"></span> ' . \Yii::t('con', 'sb_search_do') . '
        </button>
      </span>
    </div>
    </div>
</div>';
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

    public function getStdNavbarHeader(string $before): string
    {
        /** @var Base $controller */
        $controller   = \Yii::$app->controller;

        $out = '<ul class="nav navbar-nav">';

        if (!defined('INSTALLING_MODE') || INSTALLING_MODE !== true) {
            $consultation       = $controller->consultation;
            $privilegeScreening = User::havePrivilege($consultation, User::PRIVILEGE_SCREENING);
            //$privilegeAny       = User::havePrivilege($consultation, User::PRIVILEGE_ANY);
            $privilegeProposal = User::havePrivilege($consultation, User::PRIVILEGE_CHANGE_PROPOSALS);

            if ($controller->consultation) {
                if (User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
                    $icon = '<span class="glyphicon glyphicon-plus-sign"></span>';
                    $url  = UrlHelper::createUrl('/pages/list-pages');
                    $out  .= '<li class="addPage">' .
                        Html::a($icon, $url, ['title' => \Yii::t('pages', 'menu_add_btn')]) . '</li>';
                }

                $homeUrl = UrlHelper::homeUrl();
                $out     .= '<li class="active">' .
                    Html::a(\Yii::t('base', 'Home'), $homeUrl, ['id' => 'homeLink']) .
                    '</li>';

                $pages = ConsultationText::getMenuEntries($controller->site, $controller->consultation);
                foreach ($pages as $page) {
                    $options = ['class' => 'page' . $page->id];
                    $out     .= '<li>' . Html::a($page->title, $page->getUrl(), $options) . '</li>';
                }
            }

            if (!User::getCurrentUser()) {
                if (get_class($controller) == UserController::class) {
                    $backUrl = UrlHelper::createUrl('/consultation/index');
                } else {
                    $backUrl = \yii::$app->request->url;
                }
                $loginUrl   = UrlHelper::createUrl(['/user/login', 'backUrl' => $backUrl]);
                $loginTitle = \Yii::t('base', 'menu_login');
                $out        .= '<li>' . Html::a($loginTitle, $loginUrl, ['id' => 'loginLink', 'rel' => 'nofollow']) .
                    '</li>';
            }
            if (User::getCurrentUser()) {
                $link = Html::a(
                    \Yii::t('base', 'menu_account'),
                    UrlHelper::createUrl('/user/myaccount'),
                    ['id' => 'myAccountLink']
                );
                $out  .= '<li>' . $link . '</li>';

                if (get_class($controller) == UserController::class) {
                    $backUrl = UrlHelper::createUrl('/consultation/index');
                } else {
                    $backUrl = \yii::$app->request->url;
                }
                $logoutUrl   = UrlHelper::createUrl(['/user/logout', 'backUrl' => $backUrl]);
                $logoutTitle = \Yii::t('base', 'menu_logout');
                $out         .= '<li>' . Html::a($logoutTitle, $logoutUrl, ['id' => 'logoutLink']) . '</li>';
            }
            if ($privilegeScreening || $privilegeProposal) {
                $adminUrl   = UrlHelper::createUrl('/admin/motion-list/index');
                $adminTitle = \Yii::t('base', 'menu_motion_list');
                $out        .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'motionListLink']) . '</li>';
            }
            if ($privilegeScreening) {
                $todo = AdminTodoItem::getConsultationTodos($controller->consultation);
                if (count($todo) > 0) {
                    $adminUrl   = UrlHelper::createUrl('/admin/index/todo');
                    $adminTitle = \Yii::t('base', 'menu_todo') . ' (' . count($todo) . ')';
                    $out        .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'adminTodo']) . '</li>';
                }
            }
            if (User::havePrivilege($consultation, IndexController::$REQUIRED_PRIVILEGES)) {
                $adminUrl   = UrlHelper::createUrl('/admin/index');
                $adminTitle = \Yii::t('base', 'menu_admin');
                $out        .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'adminLink']) . '</li>';
            }
        }
        $out .= '</ul>';

        return $out;
    }

    public function getAntragsgruenAd(string $before): string
    {
        if (\Yii::$app->language == 'de') {
            $url = 'https://antragsgruen.de/';
        } else {
            $url = 'https://motion.tools/';
        }

        return '<div class="antragsgruenAd well">
        <div class="nav-header">' . \Yii::t('con', 'aad_title') . '</div>
        <div class="content">
            ' . \Yii::t('con', 'aad_text') . '
            <div>
                <a href="' . $url . '" title="' . \Yii::t('con', 'aad_btn') . '" class="btn btn-primary">
                <span class="glyphicon glyphicon-chevron-right"></span> Infos
                </a>
            </div>
        </div>
    </div>';
    }

    public function footerLine(string $before): string
    {
        $out = '<footer class="footer">';

        if (!defined('INSTALLING_MODE') || INSTALLING_MODE !== true) {
            $legalLink   = UrlHelper::createUrl(['/pages/show-page', 'pageSlug' => 'legal']);
            $privacyLink = UrlHelper::createUrl(['/pages/show-page', 'pageSlug' => 'privacy']);

            $out .= '<a href="' . Html::encode($legalLink) . '" class="legal" id="legalLink">' .
                \Yii::t('base', 'imprint') . '</a>
            <a href="' . Html::encode($privacyLink) . '" class="privacy" id="privacyLink">' .
                \Yii::t('base', 'privacy_statement') . '</a>';
        }

        $out .= '<span class="version">';
        if (\Yii::$app->language == 'de') {
            $out .= '<a href="https://antragsgruen.de/">Antragsgrün</a>, Version ' .
                Html::a(Html::encode(ANTRAGSGRUEN_VERSION), ANTRAGSGRUEN_HISTORY_URL);
        } else {
            $out .= '<a href="https://motion.tools/">Antragsgrün</a>, Version ' .
                Html::a(Html::encode(ANTRAGSGRUEN_VERSION), ANTRAGSGRUEN_HISTORY_URL);
        }
        $out .= '</span>';

        $out .= '</footer>';

        return $out;
    }

    /**
     * @param string $before
     * @param ConsultationMotionType[] $motionTypes
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSidebarCreateMotionButton($before, $motionTypes)
    {
        $html      = '<div class="createMotionHolder1"><div class="createMotionHolder2">';
        $htmlSmall = '';

        foreach ($motionTypes as $motionType) {
            $link        = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionType->id]);
            $description = $motionType->createTitle;

            $html      .= '<a class="createMotion createMotion' . $motionType->id . '" ' .
                'href="' . Html::encode($link) . '" title="' . Html::encode($description) . '" rel="nofollow">' .
                '<span class="glyphicon glyphicon-plus-sign"></span>' . Html::encode($description) .
                '</a>';
            $htmlSmall .=
                '<a class="navbar-brand" href="' . Html::encode($link) . '" rel="nofollow">' .
                '<span class="glyphicon glyphicon-plus-sign"></span>' . Html::encode($description) . '</a>';
        }

        $html                               .= '</div></div>';
        $this->layout->menusHtml[]          = $html;
        $this->layout->menusSmallAttachment = $htmlSmall;

        return '';
    }
}
