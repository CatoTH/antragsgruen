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

    public function getAntragsgruenAd()
    {
        if (\Yii::$app->language == 'de') {
            return '<div class="antragsgruenAd well">
        <div class="nav-header">Dein Antragsgrün</div>
        <div class="content">
            Du willst Antragsgrün selbst für deine(n) KV / LV / GJ / BAG / LAG einsetzen?
            <div>
                <a href="https://antragsgruen.de/" title="Das Antragstool selbst einsetzen" class="btn btn-primary">
                <span class="glyphicon glyphicon-chevron-right"></span> Infos
                </a>
            </div>
        </div>
    </div>';
        } else {
            return '<div class="antragsgruenAd well">
        <div class="nav-header">Using Antragsgrün</div>
        <div class="content">
            Du you want to use Antragsgrün / motion.tools for your own assemly?
            <div>
                <a href="https://motion.tools/" title="Information about using Antragsgrün" class="btn btn-primary">
                <span class="glyphicon glyphicon-chevron-right"></span> Information
                </a>
            </div>
        </div>
    </div>';
        }
    }

    public function getSearchForm()
    {
        $html = Html::beginForm(UrlHelper::createUrl('consultation/search'), 'post', ['class' => 'form-search']);
        $html .= '<div class="nav-list"><div class="nav-header">' . \Yii::t('con', 'sb_search') . '</div>
    <div style="text-align: center; padding-left: 7px; padding-right: 7px;">
    <div class="input-group">
      <input type="text" class="form-control query" name="query"
        placeholder="' . Html::encode(\Yii::t('con', 'sb_search_query')) . '" required
        title="' . Html::encode(\Yii::t('con', 'sb_search_query')) . '">
      <span class="input-group-btn">
        <button class="btn btn-default" type="submit" title="' . Html::encode(\Yii::t('con', 'sb_search_do')) . '">
            <span class="glyphicon glyphicon-search"></span> ' . \Yii::t('con', 'sb_search_do') . '
        </button>
      </span>
    </div>
    </div>
</div>';
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

        $this->layout->menusHtml[]          =
            '<div class="createMotionHolder1"><div class="createMotionHolder2">' .
            '<a class="createMotion" href="' . Html::encode($link) . '"
                    title="' . Html::encode($description) . '" rel="nofollow">' .
            '<span class="glyphicon glyphicon-plus-sign"></span>' . $description .
            '</a></div></div>';
        $this->layout->menusSmallAttachment =
            '<a class="navbar-brand" href="' . Html::encode($link) . '" rel="nofollow">' .
            '<span class="glyphicon glyphicon-plus-sign"></span>' . $description . '</a>';
    }
}
