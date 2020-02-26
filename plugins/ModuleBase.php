<?php

namespace app\plugins;

use app\models\db\{Consultation, Motion, MotionSection, Site};
use app\models\layoutHooks\Hooks;
use app\models\settings\Layout;
use app\models\siteSpecificBehavior\DefaultBehavior;
use yii\base\{Action, Module};
use yii\web\{AssetBundle, Controller, View};

class ModuleBase extends Module
{
    public function init(): void
    {
        parent::init();

        if (\Yii::$app instanceof \yii\console\Application) {
            $ref                       = new \ReflectionClass($this);
            $this->controllerNamespace = $ref->getNamespaceName() . '\\commands';
        }
    }

    /**
     * @param Controller $controller
     * @return AssetBundle[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getActiveAssetBundles(Controller $controller)
    {
        return [];
    }

    protected static function getMotionUrlRoutes(): array
    {
        return [];
    }

    public static function getManagerUrlRoutes(string $domainPlain): array
    {
        return [];
    }

    public static function getDefaultRouteOverride(): ?string
    {
        return null;
    }

    public static function getAllUrlRoutes(string $dommotion, string $dommotionOld): array
    {
        $urls = [];
        foreach (static::getMotionUrlRoutes() as $url => $route) {
            $urls[$dommotion . '/' . $url]    = $route;
            $urls[$dommotionOld . '/' . $url] = $route;
        }
        return $urls;
    }

    /**
     * @param Site $site
     * @return null|DefaultBehavior|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getSiteSpecificBehavior(Site $site)
    {
        return null;
    }

    /**
     * @param Consultation $consultation
     * @return string|\app\models\settings\Consultation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getConsultationSettingsClass(Consultation $consultation)
    {
        return null;
    }

    /**
     * @param Site $site
     * @return string|\app\models\settings\Site
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getSiteSettingsClass(Site $site)
    {
        return null;
    }

    public static function getProvidedLayouts(?View $view = null): array
    {
        return [];
    }

    public static function overridesDefaultLayout(): ?string
    {
        return null;
    }

    public static function getProvidedPdfLayouts(array $default): array
    {
        return $default;
    }

    /**
     * @param Layout $layoutSettings
     * @param Consultation $consultation
     * @return Hooks[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getForcedLayoutHooks($layoutSettings, $consultation)
    {
        return [];
    }

    public static function getCustomSiteCreateView(): ?string
    {
        return null;
    }

    public static function getRobotsIndexOverride(?Consultation $consultation, Action $action, bool $default): ?bool
    {
        return null;
    }

    public static function getDefaultLogo(): ?array
    {
        return null;
    }
}
