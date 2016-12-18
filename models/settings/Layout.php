<?php

namespace app\models\settings;

use app\models\db\Consultation;
use app\components\UrlHelper;
use app\views\hooks\LayoutHooks;
use app\views\hooks\LayoutStd;
use app\views\hooks\LayoutGruenesCi2;
use yii\helpers\Html;

class Layout
{
    const DEFAULT_LAYOUT = 'layout-classic';

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
    public $mainCssFile          = null;
    public $mainAMDModule        = null;

    /** @var LayoutHooks */
    public $hooks = null;

    /** @var \app\models\db\Consultation|null */
    private $consultation;

    /**
     * @return string[]
     */
    public static function getCssLayouts()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        return array_merge([
            'layout-classic'     => 'Antragsgrün-Standard',
            'layout-gruenes-ci'  => 'Grünes CI',
            'layout-gruenes-ci2' => 'Grünes CI v2',
            'layout-dbjr'        => 'DBJR',
        ], $params->localLayouts);
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->mainCssFile = $layout;
        switch ($layout) {
            case 'layout-gruenes-ci2':
                $this->hooks = new LayoutGruenesCi2($this, $this->consultation);
                break;
            default:
                $this->hooks = new LayoutStd($this, $this->consultation);
        }
    }

    /**
     * @param Consultation $consultation
     */
    public function setConsultation(Consultation $consultation)
    {
        $this->consultation = $consultation;
        if ($consultation && count($this->breadcrumbs) == 0) {
            if ($consultation->getForcedMotion()) {
                $this->breadcrumbs[UrlHelper::homeUrl()] = $consultation->getForcedMotion()->motionType->titleSingular;
            } else {
                $this->breadcrumbs[UrlHelper::homeUrl()] = $consultation->titleShort;
            }
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

    public function getHTMLLanguageCode()
    {
        if (!$this->consultation) {
            /** @var AntragsgruenApp $params */
            $params = \yii::$app->params;
            $lang   = explode('-', $params->baseLanguage);
            if ($lang[0] == 'de') {
                return 'de';
            } else {
                return 'en';
            }
        }
        $langs = explode(',', $this->consultation->wordingBase);
        $lang  = explode('-', $langs[0]);
        if ($lang[0] == 'de') {
            return 'de';
        } else {
            return 'en';
        }
    }

    /**
     * @return string
     */
    public function getJSLanguageCode()
    {
        if (!$this->consultation) {
            /** @var AntragsgruenApp $params */
            $params = \yii::$app->params;
            $lang   = explode('-', $params->baseLanguage);
            if ($lang[0] == 'de') {
                return 'de';
            } elseif ($params->baseLanguage == 'en-gb') {
                return 'en-gb';
            } else {
                return 'en';
            }
        }
        $langs = explode(',', $this->consultation->wordingBase);
        $lang  = explode('-', $langs[0]);
        if ($lang[0] == 'de') {
            return 'de';
        } elseif ($langs[0] == 'en-gb') {
            return 'en-gb';
        } else {
            return 'en';
        }
    }

    /**
     * @return string[]
     */
    public function getJSFiles()
    {
        $jsLang = $this->getJSLanguageCode();
        $files  = [];
        if (defined('YII_DEBUG') && YII_DEBUG && false) {
            $files[] = $this->resourceUrl('js/bootstrap.js');
            $files[] = $this->resourceUrl('js/bower/bootbox/bootbox.js');
            $files[] = $this->resourceUrl('js/scrollintoview.js');
            $files[] = $this->resourceUrl('js/jquery.isonscreen.js');
            $files[] = $this->resourceUrl('js/bower/intl/dist/Intl.min.js');
            $files[] = $this->resourceUrl('js/antragsgruen.js');
            $files[] = $this->resourceUrl('js/antragsgruen-' . $jsLang . '.js');
        } else {
            $files[] = $this->resourceUrl('js/build/antragsgruen.min.js');
            $files[] = $this->resourceUrl('js/build/antragsgruen-' . $jsLang . '.min.js');
        }
        foreach ($this->extraJs as $extraJs) {
            $files[] = $this->resourceUrl($extraJs);
        }

        return $files;
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
        $this->addJS('npm/moment-with-locales.min.js');
        $this->addJS('npm/bootstrap-datetimepicker.min.js');
        $this->addCSS('npm/bootstrap-datetimepicker.min.css');
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
        $this->addJS('npm/fuelux.min.js');
        $this->addCSS('npm/fuelux.min.css');
    }

    /**
     */
    public function loadSortable()
    {
        $this->addJS('npm/Sortable.min.js');
    }

    /**
     */
    public function loadTypeahead()
    {
        $this->addJs('npm/typeahead.bundle.min.js');
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
        $mtime    = (file_exists($absolute) ? filemtime($absolute) : 0);
        $age      = time() - $mtime;
        if ($age < 604800) { // 1 Week
            $url .= (mb_strpos($url, '?') !== false ? '&' : '?');
            $url .= $mtime;
        }
        $newUrl = $params->resourceBase . $url;
        return Html::encode($newUrl);
    }

    /**
     * @param string $module
     */
    public function setMainAMDModule($module)
    {
        $this->mainAMDModule = $module;
    }

    /**
     * @return string
     */
    public function getAMDLoader()
    {
        if ($this->mainAMDModule) {
            $module = $this->resourceUrl('js/build/' . $this->mainAMDModule);
            $src    = $this->resourceUrl('npm/require.js');
            return '<script data-main="' . addslashes($module) . '" src="' . addslashes($src) . '">';
        }
    }
}
