<?php

namespace app\plugins\green_layout;

use app\components\UrlHelper;
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
        $out = '<header class="row logo" role="banner">' .
               '<p id="logo"><a href="' . Html::encode(UrlHelper::homeUrl()) . '" class="homeLinkLogo" title="' .
               Html::encode(\Yii::t('base', 'home_back')) . '">' .
               $this->layout->getLogoStr() .
               '</a></p>' .
               '<div class="hgroup">' .
               '<div id="site-title"><span>' .
               '<a href="' . Html::encode(UrlHelper::homeUrl()) . '" rel="home">Discuss.green</a>' .
               '</span></div>';
        if ($this->consultation) {
            $out .= '<div id="site-description">' . Html::encode($this->consultation->title) . '</div>';
        }
        $out .= '</div>' .
                '</header>';

        return $out;
    }

    public function beforeContent(string $before): string
    {
        $out = '<section class="navwrap">' .
               '<nav role="navigation" class="pos" id="mainmenu"><h6 class="unsichtbar">' .
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
            $legalLink   = UrlHelper::createUrl(['/pages/show-page', 'pageSlug' => 'legal']);
            $privacyLink = UrlHelper::createUrl(['/pages/show-page', 'pageSlug' => 'privacy']);

            $out .= '<a href="' . Html::encode($legalLink) . '" class="legal" id="legalLink">' .
                    \Yii::t('base', 'imprint') . '</a>
            <a href="' . Html::encode($privacyLink) . '" class="privacy" id="privacyLink">' .
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
}
