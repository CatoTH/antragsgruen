<?php

namespace app\views\hooks;

use app\components\UrlHelper;
use yii\helpers\Html;

class LayoutGruenesCi2 extends LayoutHooks
{
    use StdFunctionTrait;

    /**
     * @return string
     */
    public function logoRow()
    {
        $out = '<header class="row logo" role="banner">' .
            '<p id="logo"><a href="' . Html::encode(UrlHelper::homeUrl()) . '" title="' .
            Html::encode(\Yii::t('base', 'home_back')) . '">' .
            $this->getLogoStr() .
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

    /**
     * @return string
     */
    public function beforeContent()
    {
        $out = '<section class="navwrap">' .
            '<nav role="navigation" class="pos" id="mainmenu"><h6 class="unsichtbar">' .
            \Yii::t('base', 'menu_main') . ':</h6>' .
            '<div class="navigation nav-fallback clearfix">';
        $out .= $this->getStdNavbarHeader();
        $out .= '</div></nav>';
        $out .= $this->breadcrumbs();
        $out .= '</section>';
        return $out;
    }

    /**
     * @return string
     */
    public function endPage()
    {
        return $this->footerLine();
    }

    /**
     * @return string
     */
    public function renderSidebar()
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
     * @return string
     */
    public function getSearchForm()
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

    /**
     * @param \app\models\db\ConsultationMotionType $motionType
     */
    public function setSidebarCreateMotionButton($motionType)
    {
        $link        = UrlHelper::createUrl(['motion/create', 'motionTypeId' => $motionType->id]);
        $description = $motionType->createTitle;

        $this->layout->menusHtml[]          = '<div class="createMotionHolder1"><div class="createMotionHolder2">' .
            '<a class="createMotion" href="' . Html::encode($link) . '"
                    title="' . Html::encode($description) . '" rel="nofollow">' .
            '<span class="glyphicon glyphicon-plus-sign"></span>' . $description .
            '</a></div></div>';
        $this->layout->menusSmallAttachment = '<a class="navbar-brand" href="' . Html::encode($link) . '" rel="nofollow">' .
            '<span class="glyphicon glyphicon-plus-sign"></span>' . $description . '</a>';
    }
}
