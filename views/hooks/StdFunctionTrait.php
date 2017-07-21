<?php

namespace app\views\hooks;

use app\components\UrlHelper;
use app\controllers\Base;
use app\controllers\UserController;
use app\models\AdminTodoItem;
use app\models\db\User;
use app\models\settings\Layout;
use yii\helpers\Html;

/**
 * Class StdFunctionTrait
 * @package views\hooks
 *
 * @property Layout $layout
 */
trait StdFunctionTrait
{
    /**
     * @return string
     */
    protected function getStdNavbarHeader()
    {
        /** @var Base $controller */
        $controller   = \Yii::$app->controller;
        $minimalistic = ($controller->consultation && $controller->consultation->getSettings()->minimalisticUI);

        $out = '<ul class="nav navbar-nav">';

        if (!defined('INSTALLING_MODE') || INSTALLING_MODE !== true) {
            if ($controller->consultation) {
                $homeUrl = UrlHelper::homeUrl();
                $out .= '<li class="active">' .
                    Html::a(\Yii::t('base', 'Home'), $homeUrl, ['id' => 'homeLink']) .
                    '</li>';
                if ($controller->consultation->hasHelpPage()) {
                    $helpLink = UrlHelper::createUrl('consultation/help');
                    $out .= '<li>' . Html::a(\Yii::t('base', 'Help'), $helpLink, ['id' => 'helpLink']) . '</li>';
                }
            } else {
                $startLink = UrlHelper::createUrl('manager/index');
                $out .= '<li class="active">' . Html::a(\Yii::t('base', 'Home'), $startLink) . '</li>';

                $helpLink = UrlHelper::createUrl('manager/help');
                $out .= '<li>' . Html::a(\Yii::t('base', 'Help'), $helpLink, ['id' => 'helpLink']) . '</li>';
            }

            if (!User::getCurrentUser() && !$minimalistic) {
                if (get_class($controller) == UserController::class) {
                    $backUrl = UrlHelper::createUrl('consultation/index');
                } else {
                    $backUrl = \yii::$app->request->url;
                }
                $loginUrl   = UrlHelper::createUrl(['user/login', 'backUrl' => $backUrl]);
                $loginTitle = \Yii::t('base', 'menu_login');
                $out .= '<li>' . Html::a($loginTitle, $loginUrl, ['id' => 'loginLink', 'rel' => 'nofollow']) . '</li>';
            }
            if (User::getCurrentUser()) {
                $link = Html::a(
                    \Yii::t('base', 'menu_account'),
                    UrlHelper::createUrl('user/myaccount'),
                    ['id' => 'myAccountLink']
                );
                $out .= '<li>' . $link . '</li>';

                $logoutUrl   = UrlHelper::createUrl(['user/logout', 'backUrl' => \yii::$app->request->url]);
                $logoutTitle = \Yii::t('base', 'menu_logout');
                $out .= '<li>' . Html::a($logoutTitle, $logoutUrl, ['id' => 'logoutLink']) . '</li>';
            }
            if (User::currentUserHasPrivilege($controller->consultation, User::PRIVILEGE_SCREENING)) {
                $adminUrl   = UrlHelper::createUrl('admin/motion/listall');
                $adminTitle = \Yii::t('base', 'menu_motion_list');
                $out .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'motionListLink']) . '</li>';
            }
            if (User::currentUserHasPrivilege($controller->consultation, User::PRIVILEGE_ANY)) {
                $todo = AdminTodoItem::getConsultationTodos($controller->consultation);
                if (count($todo) > 0) {
                    $adminUrl   = UrlHelper::createUrl('admin/index/todo');
                    $adminTitle = \Yii::t('base', 'menu_todo') . ' (' . count($todo) . ')';
                    $out .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'adminTodo']) . '</li>';
                }

                $adminUrl   = UrlHelper::createUrl('admin/index');
                $adminTitle = \Yii::t('base', 'menu_admin');
                $out .= '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'adminLink']) . '</li>';
            }
        }
        $out .= '</ul>';

        return $out;
    }

    /**
     * @return string
     */
    protected function getLogoStr()
    {
        /** @var Base $controller */
        $controller   = \Yii::$app->controller;
        $resourceBase = $controller->getParams()->resourceBase;

        if ($controller->consultation && $controller->consultation->getSettings()->logoUrl != '') {
            $path     = parse_url($controller->consultation->getSettings()->logoUrl);
            $filename = basename($path['path']);
            $filename = substr($filename, 0, strrpos($filename, '.'));
            $filename = str_replace(
                ['_', 'ue', 'ae', 'oe', 'Ue', 'Oe', 'Ae'],
                [' ', 'ü', 'ä', 'ö', 'Ü' . 'Ö', 'Ä'],
                $filename
            );
            $logoUrl  = $controller->consultation->getSettings()->logoUrl;
            if (!isset($path['host']) && $logoUrl[0] != '/') {
                $logoUrl = $resourceBase . $logoUrl;
            }
            return '<img src="' . Html::encode($logoUrl) . '" alt="' . Html::encode($filename) . '">';
        } else {
            return '<span class="logoImg"></span>';
        }
    }

    /**
     * @return string
     */
    public function breadcrumbs()
    {
        $out = '';
        if (is_array($this->layout->breadcrumbs)) {
            $out .= '<ol class="breadcrumb">';
            foreach ($this->layout->breadcrumbs as $link => $name) {
                if ($link == '' || is_null($link)) {
                    $out .= '<li>' . Html::encode($name) . '</li>';
                } else {
                    $out .= '<li>' . Html::a($name, $link) . '</li>';
                }
            }
            $out .= '</ol>';
        }

        return $out;
    }

    /**
     * @return string
     */
    public function footerLine()
    {
        /** @var Base $controller */
        $controller = \Yii::$app->controller;

        $out = '<footer class="footer"><div class="container">';

        if (!defined('INSTALLING_MODE') || INSTALLING_MODE !== true) {
            if ($controller->consultation) {
                $legalLink   = UrlHelper::createUrl('consultation/legal');
                $privacyLink = UrlHelper::createUrl('consultation/privacy');
            } else {
                $legalLink   = UrlHelper::createUrl('manager/site-legal');
                $privacyLink = UrlHelper::createUrl('manager/site-privacy');
            }

            $out .= '<a href="' . Html::encode($legalLink) . '" class="legal" id="legalLink">' .
                \Yii::t('base', 'imprint') . '</a>
            <a href="' . Html::encode($privacyLink) . '" class="privacy" id="privacyLink">' .
                \Yii::t('base', 'privacy_statement') . '</a>';
        }

        $out .= '<span class="version">';
        if (\Yii::$app->language == 'de') {
            $out .= '<a href="https://antragsgruen.de/">Antragsgrün</a>, Version ' .
                Html::a(ANTRAGSGRUEN_VERSION, ANTRAGSGRUEN_HISTORY_URL);
        } else {
            $out .= '<a href="https://motion.tools/">Antragsgrün</a>, Version ' .
                Html::a(ANTRAGSGRUEN_VERSION, ANTRAGSGRUEN_HISTORY_URL);
        }
        $out .= '</span>';

        $out .= '</div></footer>';

        return $out;
    }

    /**
     * @param \app\models\db\ConsultationMotionType $motionType
     */
    public function setSidebarCreateMotionButton($motionType)
    {
        $link        = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionType[0]->id]);
        $description = $motionType[0]->createTitle;

        $this->layout->menusHtml[]          = '<div class="createMotionHolder1"><div class="createMotionHolder2">' .
            '<a class="createMotion" href="' . Html::encode($link) . '"
                    title="' . Html::encode($description) . '" rel="nofollow">' .
            '<span class="glyphicon glyphicon-plus-sign"></span>' . $description .
            '</a></div></div>';
        $this->layout->menusSmallAttachment =
            '<a class="navbar-brand" href="' . Html::encode($link) . '" rel="nofollow">' .
            '<span class="glyphicon glyphicon-plus-sign"></span>' . $description . '</a>';
    }
}
