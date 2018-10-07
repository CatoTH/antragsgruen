<?php

namespace app\plugins\antragsgruen_sites;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\Consultation;
use app\models\db\User;
use app\models\layoutHooks\Hooks;
use yii\helpers\Html;

class LayoutHooks extends Hooks
{
    /**
     * @param $before
     * @return string
     */
    public function getStdNavbarHeader($before)
    {
        /** @var Base $controller */
        $controller = \Yii::$app->controller;
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

    /**
     * @param string $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function footerLine($before)
    {
        /** @var Base $controller */
        $controller = \Yii::$app->controller;
        if ($controller->consultation) {
            return $before;
        }

        $out = '<footer class="footer"><div class="container">';

        $legalLink   = UrlHelper::createUrl('/antragsgruen_sites/manager/legal');
        $privacyLink = UrlHelper::createUrl('/antragsgruen_sites/manager/privacy');

        $out .= '<a href="' . Html::encode($legalLink) . '" class="legal" id="legalLink">' .
            \Yii::t('base', 'imprint') . '</a>
            <a href="' . Html::encode($privacyLink) . '" class="privacy" id="privacyLink">' .
            \Yii::t('base', 'privacy_statement') . '</a>';

        $out .= '<span class="version">';
        if (\Yii::$app->language === 'de') {
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
     * @param Consultation $consultation
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAdminIndexHint($before, Consultation $consultation)
    {
        return $before . '<article class="adminCard adminCardSupport">
        <header>
            <h2>Verfügbarkeit, Support</h2>
        </header>
        <main>
            Wenn für eine Veranstaltung eine garantierte Verfügbarkeit von Antragsgrün und professioneller Support
            benötigt wird, setzt euch bitte frühzeitig <a href="https://antragsgruen.de/#support">mit uns in Kontakt</a>!
        </main>
    </article>';
    }
}
