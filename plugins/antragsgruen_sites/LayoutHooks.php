<?php

namespace app\plugins\antragsgruen_sites;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\User;
use app\models\layoutHooks\HooksAdapter;
use app\models\layoutHooks\Layout;
use yii\helpers\Html;

class LayoutHooks extends HooksAdapter
{
    /**
     * @param $before
     * @return string
     */
    public function getStdNavbarHeader($before)
    {
        /** @var Base $controller */
        $controller   = \Yii::$app->controller;
        if ($controller->consultation) {
            return $before;
        }

        $out = '<ul class="nav navbar-nav">';

        $startLink = UrlHelper::createUrl('/antragsgruen_sites/manager/index');
        $out       .= '<li class="active">' . Html::a(\Yii::t('base', 'Home'), $startLink) . '</li>';

        $helpLink = UrlHelper::createUrl('/antragsgruen_sites/manager/help');
        $out      .= '<li>' . Html::a(\Yii::t('base', 'Help'), $helpLink, ['id' => 'helpLink']) . '</li>';

        if (!User::getCurrentUser()) {
            $loginUrl   = UrlHelper::createUrl(['/user/login', 'backUrl' => \yii::$app->request->url]);
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

        $out .= '</ul>';

        return $out;
    }
}
