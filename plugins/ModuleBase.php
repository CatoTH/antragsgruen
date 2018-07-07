<?php

namespace app\plugins;

use app\models\db\Consultation;
use app\models\db\Site;
use app\models\layoutHooks\Hooks;
use app\models\settings\AntragsgruenApp;
use app\models\settings\Layout;
use app\models\siteSpecificBehavior\DefaultBehavior;
use app\plugins\memberPetitions\ConsultationSettings;
use yii\base\Module;
use yii\web\AssetBundle;
use yii\web\Controller;
use yii\web\View;

class ModuleBase extends Module
{
    /**
     */
    public function init()
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
    public static function getActiveAssetBundles($controller)
    {
        return [];
    }

    /**
     */
    protected static function getMotionUrlRoutes()
    {
        return [];
    }

    /**
     * @param string $domainPlain
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getManagerUrlRoutes($domainPlain)
    {
        return [];
    }

    /**
     * @return string
     */
    public static function getDefaultRouteOverride()
    {
        return null;
    }

    /**
     * @param string $dommotion
     * @param string $dommotionOld
     * @return array
     */
    public static function getAllUrlRoutes($dommotion, $dommotionOld)
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
    public static function getSiteSpecificBehavior($site)
    {
        return null;
    }

    /**
     * @param Consultation $consultation
     * @return string|ConsultationSettings
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getConsultationSettingsClass($consultation)
    {
        return null;
    }

    /**
     * @param View|null $view
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getProvidedLayouts($view = null)
    {
        return [];
    }

    /**
     * @return null|string
     */
    public static function overridesDefaultLayout()
    {
        return null;
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

    /**
     * @return string;
     */
    public static function getCustomSiteCreateView()
    {
        return null;
    }

    /**
     * @return string
     */
    public static function getSiteCreateView()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        foreach ($params->getPluginClasses() as $pluginClass) {
            if ($pluginClass::getCustomSiteCreateView()) {
                return $pluginClass::getCustomSiteCreateView();
            }
        }
        return "@app/plugins/dd_green_manager/views/sitedata_subdomain";
    }
}
