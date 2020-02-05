<?php

namespace app\plugins\green_manager;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\{Consultation, Site, User};
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

        $startLink = UrlHelper::createUrl('/green_manager/manager/index');
        $out       .= '<li class="active">' . Html::a(\Yii::t('base', 'Home'), $startLink) . '</li>';

        $helpLink = UrlHelper::createUrl('/green_manager/manager/help');
        $out      .= '<li>' . Html::a(\Yii::t('base', 'Help'), $helpLink, ['id' => 'helpLink']) . '</li>';

        $faqLink = UrlHelper::createUrl('/green_manager/manager/free-hosting');
        $out      .= '<li>' . Html::a('FAQ', $faqLink, ['id' => 'freeHostingLink']) . '</li>';

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

    public function footerLine(string $before): string
    {
        /** @var Base $controller */
        $controller = \Yii::$app->controller;
        if ($controller->consultation) {
            return $before;
        }

        $out = '<footer class="footer"><div class="container">';

        $legalLink   = UrlHelper::createUrl('/green_manager/manager/legal');
        $privacyLink = UrlHelper::createUrl('/green_manager/manager/privacy');

        $out .= '<a href="' . Html::encode($legalLink) . '" class="legal" id="legalLink">' .
            \Yii::t('base', 'imprint') . '</a>
            <a href="' . Html::encode($privacyLink) . '" class="privacy" id="privacyLink">' .
            \Yii::t('base', 'privacy_statement') . '</a>';

        $out .= '<span class="version">';
        $out .= '<a href="https://discuss.green/">Antragsgrün</a>, Version ' .
            Html::a(Html::encode(ANTRAGSGRUEN_VERSION), ANTRAGSGRUEN_HISTORY_URL);
        $out .= '</span>';

        $out .= '</div></footer>';

        return $out;
    }

    /**
     * @param string[] $before
     * @param Site $site
     * @return string[]
     */
    public function getSitewidePublicWarnings($before, Site $site)
    {
        /** @var SiteSettings $settings */
        $settings = $site->getSettings();
        if (!$settings->isConfirmed) {
            $before[] = '<strong>This site still runs in DEMO mode.</strong><br>' .
                'It will become permanent once the confirmation e-mail ' .
                'sent to the person who created this site has been confirmed.';
        }
        return $before;
    }

    public function getAdminIndexHint(string $before, Consultation $consultation): string
    {
        return $before . '<article class="adminCard adminCardSupport">
        <header>
            <h2>Availability, Support</h2>
        </header>
        <main>
            If you need guaranteed availability of Discuss.green or professional support,
            please <a href="https://discuss.green/#contact">contact us</a> soon!
        </main>
    </article>';
    }

    public function getAntragsgruenAd(string $before): string
    {
            return '<div class="antragsgruenAd well">
        <div class="nav-header">Using Discuss.green</div>
        <div class="content">
            Du you want to use Discuss.green for your own convention?
            <div>
                <a href="https://discuss.green/" title="Information about using Discuss.green / Antragsgrün" class="btn btn-primary">
                <span class="glyphicon glyphicon-chevron-right"></span> Information
                </a>
            </div>
        </div>
    </div>';
    }
}
