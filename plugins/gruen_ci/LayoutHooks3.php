<?php

namespace app\plugins\gruen_ci;

use app\components\UrlHelper;
use app\models\layoutHooks\{Hooks, Layout};
use yii\helpers\Html;

class LayoutHooks3 extends Hooks
{
    public function beforePage(string $before): string
    {
        $str = '<header id="pageHeader"><div class="headerContent">';

        $str .= '<div class="logoHolder">';
        $str .= '<a href="' . Html::encode(UrlHelper::homeUrl()) . '" class="homeLinkLogo">';
        $str .= '<span class="sr-only">' . \Yii::t('base', 'home_back') . '</span>';
        $str .= $this->layout->getLogoStr();
        $str .= '</a>';
        $str .= '</div>';

        $str .= '<div class="mainMenu">';
        $str .= '<nav class="navbar" aria-label="' . \Yii::t('base', 'aria_mainmenu') . '">
        <div class="navbar-inner">';
        $str .= Layout::getStdNavbarHeader();
        $str .= '</div>
        </nav>';
        $str .= '</div>';

        $str .= '<div class="titleHolder">';
        $str .= '<div class="antragsgruen">Antragsgrün</div>';
        $str .= '<div class="consultationTitle">' . Html::encode($this->consultation->title) . '</div>';
        $str .= '</div>';

        return $str . '</div></header>';
    }

    public function beginPage(string $before): string
    {
        return '';
    }

    public function logoRow(string $before): string
    {
        return '';
    }
}
