<?php

namespace app\models\settings;

use app\components\UrlHelper;
use yii\helpers\Html;

class Layout
{
    public $menu                 = [];
    public $breadcrumbs          = null;
    public $multimenu            = [];
    public $preSidebarHtml       = '';
    public $postSidebarHtml      = '';
    public $menusHtml            = [];
    public $menusHtmlSmall       = [];
    public $menusSmallAttachment = '';
    public $robotsNoindex        = false;
    public $extraCss             = [];
    public $extraJs              = [];
    public $onloadJs             = [];
    public $fullWidth            = false;
    public $fullScreen           = false;
    public $mainCssFile          = 'layout-classic';

    /** @var \app\models\db\Consultation|null */
    private $consultation;

    /**
     * @return string[]
     */
    public static function getCssLayouts()
    {
        return [
            'layout-classic'    => 'Antragsgrün-Standard',
            'layout-gruenes-ci' => 'Grünes CI',
            'layout-dbjr'       => 'DBJR',
        ];
    }

    /**
     * @param \app\models\db\Consultation $consultation
     */
    public function setConsultation(\app\models\db\Consultation $consultation)
    {
        $this->consultation = $consultation;
        if ($consultation && count($this->breadcrumbs) == 0) {
            $this->breadcrumbs[UrlHelper::createUrl('consultation/index')] = $consultation->titleShort;
        }
    }

    /**
     * @param string $file
     * @return $this;
     */
    public function addCSS($file)
    {
        $webAdd = (defined('YII_FROM_ROOTDIR') && YII_FROM_ROOTDIR === true ? 'web/' : '');
        $file   = $webAdd . $file;

        if (!in_array($file, $this->extraCss)) {
            $this->extraCss[] = $file;
        }
        return $this;
    }

    /**
     * @param string $execJs
     * @return $this;
     */
    public function addOnLoadJS($execJs)
    {
        $this->onloadJs[] = $execJs;
        return $this;
    }

    /**
     * @param string $file
     * @return $this;
     */
    public function addJS($file)
    {
        $webAdd = (defined('YII_FROM_ROOTDIR') && YII_FROM_ROOTDIR === true ? 'web/' : '');
        $file   = $webAdd . $file;

        if (!in_array($file, $this->extraJs)) {
            $this->extraJs[] = $file;
        }
        return $this;
    }

    /**
     * @param string $name
     * @param null|string $path
     * @return $this
     */
    public function addBreadcrumb($name, $path = null)
    {
        if ($path) {
            $this->breadcrumbs[$path] = $name;
        } else {
            $this->breadcrumbs[] = $name;
        }
        return $this;
    }

    /**
     */
    public function loadDatepicker()
    {
        $this->addJS('js/bower/moment/min/moment-with-locales.min.js');
        $this->addJS('js/bower/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js');
        $this->addCSS('js/bower/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css');
    }

    /**
     */
    public function loadCKEditor()
    {
        $this->addJS('js/ckeditor/ckeditor.js');
    }

    /**
     */
    public function loadFuelux()
    {
        $this->addJS('js/fuelux/js/fuelux.min.js');
        $this->addCSS('js/fuelux/css/fuelux.min.css');
    }

    /**
     */
    public function loadTypeahead()
    {
        $this->addJs('js/bower/typeahead.js/dist/typeahead.bundle.min.js');
    }

    /**
     */
    public function loadShariff()
    {
        $this->addJS('js/bower/shariff/build/shariff.min.js');
        $this->addCSS('js/bower/shariff/build/shariff.complete.css');
    }

    /**
     * @param string $htmlId
     * @return string
     */
    public function getMiniMenu($htmlId)
    {
        $dropdownHtml = '';
        foreach ($this->menusHtmlSmall as $menu) {
            $dropdownHtml .= $menu;
        }
        $out = '<nav class="navbar navbar-default sidebarSmall visible-sm-block visible-xs-block" id="' . $htmlId . '">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#sidebarSmallContent" aria-expanded="false">
                <span class="sr-only">Menü</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            ' . $this->menusSmallAttachment . '
        </div>

        <div class="collapse navbar-collapse" id="sidebarSmallContent">
            <ul class="nav navbar-nav">
                ' . $dropdownHtml . '
            </ul>
        </div>
    </div>
</nav>';
        return $out;

    }

    /**
     * @param string $url
     * @return string
     */
    public static function resourceUrl($url)
    {
        /** @var AntragsgruenApp $params */
        $params   = \yii::$app->params;
        $absolute = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR .
            str_replace('/', DIRECTORY_SEPARATOR, $url);
        $mtime    = filemtime($absolute);
        $age      = time() - $mtime;
        if ($age < 604800) { // 1 Week
            $url .= (mb_strpos($url, '?') !== false ? '&' : '?');
            $url .= $mtime;
        }
        $newUrl = $params->resourceBase . $url;
        return Html::encode($newUrl);
    }
}
