<?php

namespace app\models\layoutHooks;

use app\components\UrlHelper;
use app\controllers\admin\IndexController;
use app\controllers\Base;
use app\controllers\UserController;
use app\models\AdminTodoItem;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationText;
use app\models\db\User;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

class StdHooks extends HooksAdapter
{
    /**
     * @param $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beginPage($before)
    {
        $out = '<header id="mainmenu">';
        $out .= '<div class="navbar">
        <div class="navbar-inner">
            <div class="container">';
        $out .= Layout::getStdNavbarHeader();
        $out .= '</div>
        </div>
    </div>';

        $out .= '</header>';

        return $out;
    }

    /**
     * @param $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function logoRow($before)
    {
        $out = '<div class="row logo">
<a href="' . Html::encode(UrlHelper::homeUrl()) . '" class="homeLinkLogo text-hide">' . \Yii::t('base', 'Home');
        $out .= $this->layout->getLogoStr();
        $out .= '</a></div>';

        return $out;
    }

    /**
     * @param $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function favicons($before)
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

    /**
     * @param string $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeContent($before)
    {
        return Layout::breadcrumbs();
    }

    /**
     * @param string $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function breadcrumbs($before)
    {
        $out = '';
        if (is_array($this->layout->breadcrumbs)) {
            $out .= '<ol class="breadcrumb">';
            foreach ($this->layout->breadcrumbs as $link => $name) {
                if ($link == '' || is_null($link)) {
                    $out .= '<li>' . Html::encode($name) . '</li>';
                } else {
                    $out .= '<li>' . Html::a(Html::encode($name), $link) . '</li>';
                }
            }
            $out .= '</ol>';
        }

        return $out;
    }

    /**
     * @param $before
     * @return string
     */
    public function endPage($before)
    {
        return $before . Layout::footerLine();
    }

    /**
     * @param $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSearchForm($before)
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

    /**
     * @param $before
     * @return string
     */
    public function renderSidebar($before)
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

    /**
     * @param string $before
     * @return string
     * @throws \app\models\exceptions\Internal
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getStdNavbarHeader($before)
    {
        /** @var Base $controller */
        $controller   = \Yii::$app->controller;
        $minimalistic = ($controller->consultation && $controller->consultation->getSettings()->minimalisticUI);

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

            if (!User::getCurrentUser() && !$minimalistic) {
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

                $logoutUrl   = UrlHelper::createUrl(['/user/logout', 'backUrl' => \yii::$app->request->url]);
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

    /**
     * @param $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAntragsgruenAd($before)
    {
        if (\Yii::$app->language == 'de') {
            return '<div class="antragsgruenAd well">
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
        } else {
            return '<div class="antragsgruenAd well">
        <div class="nav-header">Using Antragsgrün</div>
        <div class="content">
            Du you want to use Antragsgrün / motion.tools for your own assembly?
            <div>
                <a href="https://motion.tools/" title="Information about using Antragsgrün" class="btn btn-primary">
                <span class="glyphicon glyphicon-chevron-right"></span> Information
                </a>
            </div>
        </div>
    </div>';
        }
    }

    /**
     * @param string $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function footerLine($before)
    {
        $out = '<footer class="footer"><div class="container">';

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

        $out .= '</div></footer>';

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
