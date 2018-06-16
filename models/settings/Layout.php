<?php

namespace app\models\settings;

use app\components\MessageSource;
use app\controllers\Base;
use app\models\db\Consultation;
use app\components\UrlHelper;
use app\models\exceptions\Internal;
use app\models\layoutHooks\StdHooks;
use yii\helpers\Html;
use yii\web\AssetBundle;
use yii\web\View;

class Layout
{
    public $menu                 = [];
    public $breadcrumbs          = [];
    public $multimenu            = [];
    public $preSidebarHtml       = '';
    public $postSidebarHtml      = '';
    public $menusHtml            = [];
    public $menusHtmlSmall       = [];
    public $menusSmallAttachment = '';
    public $robotsNoindex        = false;
    public $extraCss             = [];
    public $extraJs              = [];
    public $bodyCssClasses       = [];
    public $onloadJs             = [];
    public $fullWidth            = false;
    public $fullScreen           = false;
    public $mainCssFile          = null;
    public $mainAMDModules       = [];
    public $canonicalUrl         = null;
    public $alternateLanuages    = [];
    public $feeds                = [];

    /** @var \app\models\db\Consultation|null */
    protected $consultation;

    /**
     * @return string[]
     */
    public static function getCssLayouts()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;

        $pluginLayouts = [];
        foreach ($params->getPluginClasses() as $pluginId => $pluginClass) {
            foreach ($pluginClass::getProvidedLayouts() as $layoutId => $layout) {
                $pluginLayouts['layout-plugin-' . $pluginId . '-' . $layoutId] = $layout['title'];
            }
        }

        return array_merge([
            'layout-classic'     => 'Antragsgrün-Standard',
            'layout-dbjr'        => 'DBJR',
        ], $params->localLayouts, $pluginLayouts);
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->mainCssFile = $layout;
        \app\models\layoutHooks\Layout::addHook(new StdHooks($this, $this->consultation));

        /** @var AntragsgruenApp $params */
        $params  = \Yii::$app->params;
        $plugins = $params->getPluginClasses();
        foreach ($plugins as $pluginId => $plugin) {
            foreach ($plugin::getProvidedLayouts() as $layoutId => $layoutDef) {
                if ($layout === 'layout-plugin-' . $pluginId . '-' . $layoutId) {
                    if (isset($layoutDef['hooks']) && $layoutDef['hooks']) {
                        $hook = new $layoutDef['hooks']($this, $this->consultation);
                        \app\models\layoutHooks\Layout::addHook($hook);
                    }
                }
            }

            foreach ($plugin::getForcedLayoutHooks($this, $this->consultation) as $hook) {
                \app\models\layoutHooks\Layout::addHook($hook);
            }
        }
    }

    /**
     * @return string
     */
    public static function getDefaultLayout()
    {
        /** @var AntragsgruenApp $params */
        $params  = \Yii::$app->params;
        $plugins = $params->getPluginClasses();
        foreach ($plugins as $plugin) {
            if ($plugin::overridesDefaultLayout()) {
                return $plugin::overridesDefaultLayout();
            }
        }
        return 'layout-classic';
    }

    /**
     */
    public function setFallbackLayoutIfNotInitializedYet()
    {
        if ($this->mainCssFile === null) {
            $this->setLayout(Layout::getDefaultLayout());
        }
    }

    /**
     * @param View $view
     * @throws Internal
     */
    public function setPluginLayout($view)
    {
        $parts = explode('-', $this->mainCssFile);
        if (count($parts) !== 4) {
            throw new Internal('Invalid layout string: ' . $this->mainCssFile);
        }

        /** @var AntragsgruenApp $params */
        $params  = \Yii::$app->params;
        $plugins = $params->getPluginClasses();
        if (!isset($plugins[$parts[2]])) {
            throw new Internal('Plugin not found');
        }
        $layouts = $plugins[$parts[2]]::getProvidedLayouts();
        if (!isset($layouts[$parts[3]])) {
            throw new Internal('Plugin layout not found');
        }

        /** @var AssetBundle $bundle */
        $bundle = $layouts[$parts[3]]['bundle'];
        $bundle::register($view);
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
        if ($consultation) {
            $language = substr($consultation->wordingBase, 0, 2);
            if ($language && isset(MessageSource::getBaseLanguages()[$language])) {
                \Yii::$app->language = $language;
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

    /**
     * @return string
     */
    public function getHTMLLanguageCode()
    {
        if (!$this->consultation) {
            /** @var AntragsgruenApp $params */
            $params = \yii::$app->params;
            $lang   = explode('-', $params->baseLanguage);
            if (isset(MessageSource::getBaseLanguages()[$lang[0]])) {
                return $lang[0];
            } else {
                return 'en';
            }
        }
        $langs = explode(',', $this->consultation->wordingBase);
        $lang  = explode('-', $langs[0]);
        if (isset(MessageSource::getBaseLanguages()[$lang[0]])) {
            return $lang[0];
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
            if ($params->baseLanguage == 'en-gb') {
                return 'en-gb';
            } else {
                return $lang[0];
            }
        }
        $langs = explode(',', $this->consultation->wordingBase);
        $lang  = explode('-', $langs[0]);
        if ($langs[0] == 'en-gb') {
            return 'en-gb';
        } else {
            return $lang[0];
        }
    }

    /**
     * @return string[]
     */
    public function getJSFiles()
    {
        $jsLang  = $this->getJSLanguageCode();
        $files   = [];
        $files[] = $this->resourceUrl('js/build/antragsgruen.min.js');
        $files[] = $this->resourceUrl('js/antragsgruen-' . $jsLang . '.js');
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
    public function loadBootstrapToggle()
    {
        $this->addJS('npm/bootstrap-toggle.min.js');
        $this->addCSS('npm/bootstrap-toggle.min.css');
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
     * @param View $view
     */
    public function registerPluginAssets($view)
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        foreach ($params->getPluginClasses() as $pluginClass) {
            foreach ($pluginClass::getActiveAssetBundles() as $assetBundle) {
                $assetBundle::register($view);
            }
        }
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
                <span class="sr-only">' . \Yii::t('base', 'menu_main') . '</span>
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
    public function addAMDModule($module)
    {
        $this->mainAMDModules[] = $module;
    }

    /**
     * @return string
     */
    public function getAMDLoader()
    {
        /** @var AntragsgruenApp $params */
        $params   = \yii::$app->params;
        $resourceBase = $params->resourceBase;
        $module = $this->resourceUrl('js/build/Antragsgruen.js');
        $src    = $this->resourceUrl('npm/require.js');
        return '<script src="' . addslashes($src) . '"></script>' .
            '<script src="' . addslashes($module) . '" id="antragsgruenScript" ' .
            'data-resource-base="' . Html::encode($resourceBase) . '"></script>';
    }

    /**
     * @return string
     */
    public function getAMDClasses()
    {
        $out = '';
        foreach ($this->mainAMDModules as $module) {
            $out .= '<span data-antragsgruen-load-class="' . Html::encode($module) . '"></span>' . "\n";
        }
        return $out;
    }

    /**
     * @param string $title
     * @return string
     */
    public function formatTitle($title)
    {
        if (stripos($title, 'Antragsgrün') === false) {
            if ($title === '') {
                $title = 'Antragsgrün';
            } elseif ($title[strlen($title) - 1] === ')') {
                $title = substr($title, 0, strlen($title) - 1) . ', Antragsgrün)';
            } else {
                $title .= ' (Antragsgrün)';
            }
        }
        return $title;
    }

    /**
     * @return string
     */
    public function getLogoStr()
    {
        /** @var Base $controller */
        $controller   = \Yii::$app->controller;
        $resourceBase = $controller->getParams()->resourceBase;

        if ($controller->consultation && $controller->consultation->getSettings()->logoUrl != '') {
            $path     = parse_url($controller->consultation->getSettings()->logoUrl);
            $filename = basename($path['path']);
            $filename = substr($filename, 0, strrpos($filename, '.'));
            $filename = str_replace(
                ['_', 'ue', 'ae', 'oe', 'Ue', 'Oe', 'Ae'],
                [' ', 'ü', 'ä', 'ö', 'Ü' . 'Ö', 'Ä'],
                $filename
            );
            $logoUrl  = $controller->consultation->getSettings()->logoUrl;
            if (!isset($path['host']) && $logoUrl[0] != '/') {
                $logoUrl = $resourceBase . $logoUrl;
            }
            return '<img src="' . Html::encode($logoUrl) . '" alt="' . Html::encode($filename) . '">';
        } else {
            return '<span class="logoImg"></span>';
        }
    }
}
