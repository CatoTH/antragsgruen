<?php

namespace app\plugins\dd_green_layout;

use app\components\UrlHelper;
use app\models\layoutHooks\HooksAdapter;
use app\models\layoutHooks\Layout;
use yii\helpers\Html;

class LayoutHooks extends HooksAdapter
{
    /**
     * @param $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beginPage($before)
    {
        return '';
    }

    /**
     * @param $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function renderSidebar($before)
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

    /**
     * @param $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function logoRow($before)
    {
        $out = '<header class="row logo" role="banner">' .
            '<p id="logo"><a href="' . Html::encode(UrlHelper::homeUrl()) . '" class="homeLinkLogo" title="' .
            Html::encode(\Yii::t('base', 'home_back')) . '">' .
            $this->layout->getLogoStr() .
            '</a></p>' .
            '<div class="hgroup">' .
            '<div id="site-title"><span>' .
            '<a href="' . Html::encode(UrlHelper::homeUrl()) . '" rel="home">Democratic Documents</a>' .
            '</span></div>';
        if ($this->consultation) {
            $out .= '<div id="site-description">' . Html::encode($this->consultation->title) . '</div>';
        }
        $out .= '</div>' .
            '</header>';

        return $out;
    }

    /**
     * @param $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeContent($before)
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

    /**
     * @param $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSearchForm($before)
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
