<?php

namespace app\plugins\antragsgruen_sites;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\{Consultation, User};
use app\models\layoutHooks\Hooks;
use yii\helpers\Html;

class LayoutHooks extends Hooks
{
    public function getStdNavbarHeader(string $before): string
    {
        /** @var Base $controller */
        $controller = \Yii::$app->controller;
        if ($controller->consultation) {
            return $before;
        }

        $out = '<ul class="nav navbar-nav">';

        $startLink = UrlHelper::createUrl('/antragsgruen_sites/manager/index');
        $out       .= '<li class="active">' . Html::a(\Yii::t('base', 'Home'), $startLink, ['aria-label' => \Yii::t('base', 'home_back')]) . '</li>';

        $helpLink = UrlHelper::createUrl('/antragsgruen_sites/manager/help');
        $helpTitle = \Yii::t('base', 'Help');
        $out      .= '<li>' . Html::a($helpTitle, $helpLink, ['id' => 'helpLink', 'aria-label' => $helpTitle]) . '</li>';

        if (!User::getCurrentUser()) {
            $loginUrl   = UrlHelper::createUrl(['/user/login', 'backUrl' => \yii::$app->request->url]);
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

            $logoutUrl   = UrlHelper::createUrl(['/user/logout', 'backUrl' => \yii::$app->request->url]);
            $logoutTitle = \Yii::t('base', 'menu_logout');
            $out         .= '<li>' . Html::a($logoutTitle, $logoutUrl, ['id' => 'logoutLink', 'aria-label' => $logoutTitle]) . '</li>';
        }

        if (\Yii::$app->language === 'de') {
            $out .= '<li><a lang="en" id="enLink" href="https://motion.tools/" aria-label="This site in english" title="This site in english">🇬🇧</a></li>';
        } else {
            $out .= '<li><a lang="en" id="deLink" href="https://antragsgruen.de/" aria-label="This site in german" title="This site in german">🇩🇪</a></li>';
        }

        $out .= '</ul>';

        return $out;
    }

    public function footerLine(string $before): string
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
            $ariaVersion = str_replace('%VERSION%', ANTRAGSGRUEN_VERSION, \Yii::t('base', 'aria_version_hint'));
            $out         .= '<a href="https://antragsgruen.de/" aria-label="' . \Yii::t('base', 'aria_antragsgruen') . '">Antragsgrün</a>, Version ' .
                            Html::a(Html::encode(ANTRAGSGRUEN_VERSION), ANTRAGSGRUEN_HISTORY_URL, ['aria-label' => $ariaVersion]);
        } else {
            $ariaVersion = str_replace('%VERSION%', ANTRAGSGRUEN_VERSION, \Yii::t('base', 'aria_version_hint'));
            $out         .= '<a href="https://motion.tools/" aria-label="' . \Yii::t('base', 'aria_antragsgruen') . '">Antragsgrün</a>, Version ' .
                            Html::a(Html::encode(ANTRAGSGRUEN_VERSION), ANTRAGSGRUEN_HISTORY_URL, ['aria-label' => $ariaVersion]);
        }
        $out .= '</span>';

        $out .= '</div></footer>';

        return $out;
    }

    public function getAdminIndexHint(string $before, Consultation $consultation): string
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

    public function endOfHead(string $before): string
    {
        if ($this->consultation) {
            $cssFile = __DIR__ . '/consultationCss/consultation-' . $this->consultation->id . '.css';
            if (file_exists($cssFile)) {
                $before .= '<style>' . file_get_contents($cssFile) . '</style>';
            }
        }
        return $before;
    }
}
