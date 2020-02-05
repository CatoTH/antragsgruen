<?php

namespace app\plugins\gruen_ci;

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
            '<a href="' . Html::encode(UrlHelper::homeUrl()) . '" rel="home">Antragsgrün</a>' .
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

    <button type="submit" class="button-submit">
                <span class="fa fa-search"></span> <span class="text">Suchen</span>
            </button>';
        $html .= Html::endForm();
        return $html;
    }
}
