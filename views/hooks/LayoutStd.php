<?php

namespace app\views\hooks;

use app\components\UrlHelper;
use app\controllers\Base;
use yii\helpers\Html;

class LayoutStd extends LayoutHooks
{
    use StdFunctionTrait;

    /**
     * @return string
     */
    public function beginPage()
    {
        return $this->getStdNavbarHeader();
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
}
