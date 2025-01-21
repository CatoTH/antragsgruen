<?php

namespace app\models\settings;

use app\components\RequestContext;
use app\components\yii\MessageSource;
use app\controllers\Base;
use app\models\db\Consultation;
use app\components\UrlHelper;
use app\models\exceptions\Internal;
use app\models\layoutHooks\{Hooks, StdHooks};
use app\plugins\ModuleBase;
use yii\base\Action;
use yii\helpers\Html;
use yii\web\{AssetBundle, Controller, View};

class Layout
{
    public const SIDEBAR_TYPE_CONSULTATION = 0;
    public const SIDEBAR_TYPE_MOTION = 1;
    public const SIDEBAR_TYPE_AMENDMENT = 2;

    public array $menu = [];
    public array $breadcrumbs = [];
    public array $multimenu = [];
    public string $preSidebarHtml = '';
    public string $postSidebarHtml = '';
    public array $menusHtml = [];
    public array $menusHtmlSmall = [];
    public ?int $menuSidebarType = null;
    public string $menusSmallAttachment = '';
    public bool $robotsNoindex = false;
    public ?string $ogImage = null;
    public array $extraCss = [];
    public array $inlineCss = [];
    public array $extraJs = [];
    public array $vueTemplates = [];
    public array $bodyCssClasses = [];
    public array $onloadJs = [];
    public bool $fullWidth = false;
    public bool $fullScreen = false;
    public ?string $mainCssFile = null;
    public array $mainAMDModules = [];
    public bool $provideJwt = false;
    public ?string $canonicalUrl = null;
    public array $alternateLanuages = [];
    public array $feeds = [];

    /** @var array<array{role: string, channel: string}> */
    public array $connectLiveEvents = [];

    protected ?Consultation $consultation = null;

    /**
     * @return string[][]
     */
    public static function getCssLayouts(?View $view = null): array
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

        return array_merge([
            'layout-classic' => [
                'id'      => 'layout-classic',
                'title'   => 'Standard',
                'preview' => AntragsgruenApp::getInstance()->resourceBase . 'img/layout-preview-std.png',
            ],
            'layout-dbjr'    => [
                'id'      => 'layout-dbjr',
                'title'   => 'DBJR',
                'preview' => AntragsgruenApp::getInstance()->resourceBase . 'img/layout-preview-dbjr.png',
            ],
        ], $pluginLayouts);
    }

    /**
     * @return array{title: string, preview: string|null, bundle: class-string, hooks?: class-string<Hooks>, odtTemplate?: string}|null
     */
    public static function getLayoutPluginDef(string $layout): ?array
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

    public function setLayout(string $layout): void
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

    public static function getDefaultLayout(): string
    {
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            if ($plugin::overridesDefaultLayout()) {
                return $plugin::overridesDefaultLayout();
            }
        }
        return 'layout-classic';
    }

    public function setFallbackLayoutIfNotInitializedYet(): void
    {
        if ($this->mainCssFile === null) {
            $this->setLayout(Layout::getDefaultLayout());
        }
    }

    public function setPluginLayout(View $view): void
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

    public function setConsultation(Consultation $consultation): void
    {
        $this->consultation = $consultation;
        if (count($this->breadcrumbs) === 0) {
            if ($consultation->getForcedMotion()) {
                $this->breadcrumbs[UrlHelper::homeUrl()] = $consultation->getForcedMotion()->motionType->titleSingular;
            } else {
                $this->breadcrumbs[UrlHelper::homeUrl()] = $consultation->titleShort;
            }
        }
        $language = substr($consultation->wordingBase, 0, 2);
        if ($language && isset(MessageSource::getBaseLanguages()[$language])) {
            \Yii::$app->language = $language;
        }
    }

    public function addCSS(string $file): self
    {
        $webAdd = (defined('YII_FROM_ROOTDIR') && YII_FROM_ROOTDIR === true ? 'web/' : '');
        $file   = $webAdd . $file;

        if (!in_array($file, $this->extraCss)) {
            $this->extraCss[] = $file;
        }
        return $this;
    }

    public function addInlineCss(string $css): self
    {
        $this->inlineCss[] = $css;
        return $this;
    }

    public function addOnLoadJS(string $execJs): self
    {
        $this->onloadJs[] = $execJs;
        return $this;
    }

    private bool $tooltipOnloadJsInitialized = false;

    public function addTooltopOnloadJs(): self
    {
        if (!$this->tooltipOnloadJsInitialized) {
            $this->tooltipOnloadJsInitialized = true;
            $this->addOnLoadJS('$(\'[data-toggle="tooltip"]\').tooltip();');
        }

        return $this;
    }

    public function addJS(string $file): self
    {
        $webAdd = (defined('YII_FROM_ROOTDIR') && YII_FROM_ROOTDIR === true ? 'web/' : '');
        $file   = $webAdd . $file;

        if (!in_array($file, $this->extraJs)) {
            $this->extraJs[] = $file;
        }
        return $this;
    }

    public function addVueTemplate(string $template): self
    {
        if (!in_array($template, $this->vueTemplates)) {
            $this->vueTemplates[] = $template;
        }
        return $this;
    }

    public function addLiveEventSubscription(string $role, string $channel): void
    {
        $this->connectLiveEvents[] = ['role' => $role, 'channel' => $channel];
    }

    public function getHTMLLanguageCode(): string
    {
        if (!$this->consultation) {
            $lang   = explode('-', AntragsgruenApp::getInstance()->baseLanguage);
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

    public function getJSLanguageCode(): string
    {
        if (!$this->consultation) {
            $lang   = explode('-', AntragsgruenApp::getInstance()->baseLanguage);
            if (AntragsgruenApp::getInstance()->baseLanguage == 'en-gb') {
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
    public function getJSFiles(): array
    {
        $jsLang  = $this->getJSLanguageCode();
        $files   = [];
        $files[] = $this->resourceUrl('js/build/antragsgruen.min.js');
        $files[] = $this->resourceUrl('js/build/antragsgruen-' . $jsLang . '.min.js');
        foreach ($this->extraJs as $extraJs) {
            $files[] = $this->resourceUrl($extraJs);
        }

        return $files;
    }

    public function addBreadcrumb(string $name, ?string $path = null): self
    {
        if ($path) {
            $this->breadcrumbs[$path] = $name;
        } else {
            $this->breadcrumbs[] = $name;
        }
        return $this;
    }

    public function loadDatepicker(): void
    {
        $this->addJS('npm/moment-with-locales.min.js');
        $this->addJS('js/build/bootstrap-datetimepicker.min.js');
        $this->addCSS('css/bootstrap-datetimepicker.min.css');
    }

    public function loadCKEditor(): void
    {
        $this->addJS('js/ckeditor/ckeditor.js');
    }

    public function loadBootstrapToggle(): void
    {
        $this->addJS('npm/bootstrap-toggle.min.js');
        $this->addCSS('npm/bootstrap-toggle.min.css');
    }

    public function loadSortable(): void
    {
        $this->addJS('npm/Sortable.min.js');
    }

    public function loadVue(): void
    {
        $this->addJS('npm/vue.global.prod.js');
    }

    public function addFullscreenTemplates(): void
    {
        $this->addVueTemplate('@app/views/shared/fullscreen-projector.vue.php');
        $this->addVueTemplate('@app/views/shared/fullscreen-imotion.vue.php');
        $this->addVueTemplate('@app/views/speech/_speech_common_mixins.vue.php');
        $this->addVueTemplate('@app/views/speech/user-fullscreen-widget.vue.php');
    }

    public function loadTypeahead(): void
    {
        $this->addJs('npm/typeahead.bundle.min.js');
    }

    public function loadSelectize(): void
    {
        $this->addJs('npm/selectize.min.js');
        $this->addCSS('css/selectize.bootstrap3.css');
    }

    public function loadVueDraggable(): void
    {
        $this->addJs('npm/vuedraggable.umd.min.js');
    }

    public function registerPluginAssets(View $view, Controller $controller): void
    {
        foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
            foreach ($pluginClass::getActiveAssetBundles($controller) as $assetBundle) {
                $assetBundle::register($view);
            }
        }
    }

    public function getMiniMenu(string $htmlId): string
    {
        $dropdownHtml = implode('', $this->menusHtmlSmall);

        $barBtn = '';
        $collapsed = '';
        if ($dropdownHtml !== '') {
            $barBtn .= '<button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#sidebarSmallContent" aria-expanded="false">
                <span class="sr-only">' . \Yii::t('base', 'menu_main') . '</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>';

            $collapsed = '<div class="collapse navbar-collapse" id="sidebarSmallContent">
                <div>
                    ' . $dropdownHtml . '
                </div>
            </div>';
        }

        return '<nav class="navbar navbar-default sidebarSmall visible-sm-block visible-xs-block" id="' . $htmlId . '">
    <div class="container-fluid">
        <div class="navbar-header">
            ' . $this->menusSmallAttachment . $barBtn . '
        </div>
        ' . $collapsed . '
    </div>
</nav>';
    }

    public static function resourceUrl(string $url): string
    {
        $absolute = \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR .
            str_replace('/', DIRECTORY_SEPARATOR, $url);
        $mtime    = (file_exists($absolute) ? filemtime($absolute) : 0);
        $age      = time() - $mtime;
        if ($age < 604800) { // 1 Week
            $url .= (str_contains($url, '?') ? '&' : '?');
            $url .= $mtime;
        }
        $newUrl = AntragsgruenApp::getInstance()->resourceBase . $url;
        return Html::encode($newUrl);
    }

    public function addAMDModule(string $module): void
    {
        $this->mainAMDModules[] = $module;
    }

    public function getAMDLoader(): string
    {
        $resourceBase = AntragsgruenApp::getInstance()->resourceBase;
        $module       = $this->resourceUrl('js/build/Antragsgruen.js');
        $src          = $this->resourceUrl('npm/require.js');
        return '<script src="' . addslashes($src) . '"></script>' .
            '<script src="' . addslashes($module) . '" id="antragsgruenScript" ' .
            'data-resource-base="' . Html::encode($resourceBase) . '"></script>';
    }

    public function getAMDClasses(): string
    {
        $out = '';
        foreach ($this->mainAMDModules as $module) {
            $out .= '<span data-antragsgruen-load-class="' . Html::encode($module) . '"></span>' . "\n";
        }
        return $out;
    }

    public function formatTitle(string $title): string
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

    public function getLogoStr(): string
    {
        /** @var Base $controller */
        $controller   = RequestContext::getWebApplication()->controller;

        if ($controller->consultation && $controller->consultation->getSettings()->logoUrl) {
            $path     = parse_url($controller->consultation->getSettings()->logoUrl);
            $logoUrl  = $controller->consultation->getSettings()->logoUrl;
            if (!isset($path['host']) && $logoUrl[0] !== '/') {
                $logoUrl = AntragsgruenApp::getInstance()->resourceBase . $logoUrl;
            }
            return '<img src="' . Html::encode($logoUrl) . '" alt="' . Html::encode(\Yii::t('base', 'logo_current')) . '">';
        } else {
            return '<span class="logoImg"></span>';
        }
    }

    protected function isRobotsIndexDefault(Action $action): bool
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

    public function isRobotsIndex(Action $action): bool
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
