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

        $out = '<header id="mainmenu">';
        $out .= '<div class="navbar">
        <div class="navbar-inner">
            <div class="container">';

        $out .= '<ul class="nav navbar-nav">';

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
        $out .= '</ul>
            </div>
        </div>
    </div>';

        $out .= '</header>';

        return $out;
    }
}
