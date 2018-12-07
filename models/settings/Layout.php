<?php

namespace app\models\settings;

use app\components\yii\MessageSource;
use app\controllers\Base;
use app\models\db\Consultation;
use app\components\UrlHelper;
use app\models\db\ConsultationFile;
use app\models\exceptions\Internal;
use app\models\layoutHooks\StdHooks;
use yii\base\Action;
use yii\helpers\Html;
use yii\web\AssetBundle;
use yii\web\Controller;
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
     * @param View|null $view
     * @return string[]
     */
    public static function getCssLayouts($view = null)
    {
        $pluginLayouts = [];
        foreach (AntragsgruenApp::getActivePlugins() as $pluginId => $pluginClass) {
            foreach ($pluginClass::getProvidedLayouts($view) as $layoutId => $layout) {
                $pluginLayouts['layout-plugin-' . $pluginId . '-' . $layoutId] = [
                    'id'      => 'layout-plugin-' . $pluginId . '-' . $layoutId,
                    'title'   => $layout['title'],
                    'preview' => $layout['preview'],
                ];
            }
        }

        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;

        return array_merge([
            'layout-classic' => [
                'id'      => 'layout-classic',
                'title'   => 'Standard',
                'preview' => $params->resourceBase . 'img/layout-preview-std.png',
            ],
            'layout-dbjr'    => [
                'id'      => 'layout-dbjr',
                'title'   => 'DBJR',
                'preview' => $params->resourceBase . 'img/layout-preview-dbjr.png',
            ],
        ], $pluginLayouts);
    }

    /**
     * @param string $layout
     * @return array|null
     */
    public static function getLayoutPluginDef($layout)
    {
        foreach (AntragsgruenApp::getActivePlugins() as $pluginId => $plugin) {
            foreach ($plugin::getProvidedLayouts(null) as $layoutId => $layoutDef) {
                if ($layout === 'layout-plugin-' . $pluginId . '-' . $layoutId) {
                    return $layoutDef;
                }
            }
        }
        return null;
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->mainCssFile = $layout;
        \app\models\layoutHooks\Layout::addHook(new StdHooks($this, $this->consultation));

        $layoutDef = static::getLayoutPluginDef($layout);
        if ($layoutDef && isset($layoutDef['hooks']) && $layoutDef['hooks']) {
            $hook = new $layoutDef['hooks']($this, $this->consultation);
            \app\models\layoutHooks\Layout::addHook($hook);
        }

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
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
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
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

        $plugins = AntragsgruenApp::getActivePlugins();
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
        if ($consultation && count($this->breadcrumbs) === 0) {
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
     * @param Controller $controller
     */
    public function registerPluginAssets($view, $controller)
    {
        foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
            foreach ($pluginClass::getActiveAssetBundles($controller) as $assetBundle) {
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
        $params       = \yii::$app->params;
        $resourceBase = $params->resourceBase;
        $module       = $this->resourceUrl('js/build/Antragsgruen.js');
        $src          = $this->resourceUrl('npm/require.js');
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

        if ($controller->consultation && $controller->consultation->getSettings()->logoUrl !== '') {
            $path     = parse_url($controller->consultation->getSettings()->logoUrl);
            $filename = basename($path['path']);
            $filename = substr($filename, 0, strrpos($filename, '.'));
            $filename = str_replace(
                ['_', 'ue', 'ae', 'oe', 'Ue', 'Oe', 'Ae'],
                [' ', 'ü', 'ä', 'ö', 'Ü' . 'Ö', 'Ä'],
                $filename
            );
            $logoUrl  = $controller->consultation->getSettings()->logoUrl;
            if (!isset($path['host']) && $logoUrl[0] !== '/') {
                $logoUrl = $resourceBase . $logoUrl;
            }
            return '<img src="' . Html::encode($logoUrl) . '" alt="' . Html::encode($filename) . '">';
        } else {
            return '<span class="logoImg"></span>';
        }
    }

    /**
     * @param Action $action
     * @return bool
     */
    protected function isRobotsIndexDefault($action)
    {
        if (AntragsgruenApp::getInstance()->mode === 'sandbox') {
            return false;
        }
        if ($this->consultation && $this->consultation->getSettings()->maintenanceMode) {
            return false;
        }
        if ($this->robotsNoindex) {
            return false;
        }

        if (!$this->consultation) {
            // These are manager pages, generally aimed to advertise the installation
            return true;
        }

        switch ($this->consultation->getSettings()->robotsPolicy) {
            case \app\models\settings\Consultation::ROBOTS_ALL:
                return true;
            case \app\models\settings\Consultation::ROBOTS_NONE:
                return false;
            case \app\models\settings\Consultation::ROBOTS_ONLY_HOME:
            default:
                if ($action->controller->id === 'consultation' && $action->id === 'home') {
                    return true;
                } else {
                    return false;
                }
        }
    }

    /**
     * @param Action $action
     * @return bool
     */
    public function isRobotsIndex($action)
    {
        $visible = $this->isRobotsIndexDefault($action);
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $override = $plugin::getRobotsIndexOverride($this->consultation, $action, $visible);
            if ($override !== null) {
                $visible = $override;
            }
        }
        return $visible;
    }
}
