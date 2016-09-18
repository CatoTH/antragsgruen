<?php

namespace app\views\hooks;

use app\components\UrlHelper;
use yii\helpers\Html;

class LayoutStd extends LayoutHooks
{
    use StdFunctionTrait;

    /**
     * @return string
     */
    public function beginPage()
    {
        $out = '<header id="mainmenu">';
        $out .= '<div class="navbar">
        <div class="navbar-inner">
            <div class="container">';
        $out .= $this->getStdNavbarHeader();
        $out .= '</div>
        </div>
    </div>';

        $out .= '</header>';

        return $out;
    }

    /**
     * @return string
     */
    public function logoRow()
    {
        $out = '<div class="row logo">
<a href="' . Html::encode(UrlHelper::homeUrl()) . '" class="homeLinkLogo text-hide">' . \Yii::t('base', 'Home');
        $out .= $this->getLogoStr();
        $out .= '</a></div>';

        return $out;
    }

    /**
     * @return string
     */
    public function beforeContent()
    {
        return $this->breadcrumbs();
    }

    /**
     * @return string
     */
    public function endPage()
    {
        return $this->footerLine();
    }
}
