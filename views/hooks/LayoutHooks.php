<?php

namespace app\views\hooks;

use app\components\UrlHelper;
use app\models\settings\Layout;
use yii\helpers\Html;

abstract class LayoutHooks
{
    /** @var Layout */
    protected $layout;

    /**
     * LayoutHooks constructor.
     * @param Layout $layout
     */
    public function __construct(Layout $layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return string
     */
    public function beforePage()
    {
        return '';
    }

    /**
     * @return string
     */
    public function beginPage()
    {
        return '';
    }

    /**
     * @return string
     */
    public function logoRow()
    {
        return '';
    }

    /**
     * @return string
     */
    public function beforeContent()
    {
        return '';
    }

    /**
     * @return string
     */
    public function afterContent()
    {
        return '';
    }

    /**
     * @return string
     */
    public function beginContent()
    {
        return '';
    }

    /**
     * @return string
     */
    public function endPage()
    {
        return '';
    }

    /**
     * @return string
     */
    public function renderSidebar()
    {
        $str = $this->layout->preSidebarHtml;
        if (count($this->layout->menusHtml) > 0) {
            $str .= '<div class="well hidden-xs">';
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
     * @return string
     */
    public function getAntragsgruenAd()
    {
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
    }
}
