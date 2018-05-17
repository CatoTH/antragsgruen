<?php

namespace app\plugins;

use app\models\db\Consultation;
use app\models\db\Site;
use app\models\siteSpecificBehavior\DefaultBehavior;
use app\plugins\memberPetitions\ConsultationSettings;
use yii\base\Module;
use yii\web\AssetBundle;

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
     * @return AssetBundle[]
     */
    public static function getActiveAssetBundles()
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
     * @param string $category
     * @return string|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getMessagePath($category)
    {
        return null;
    }

    /**
     * @return array
     */
    public static function getProvidedLayouts()
    {
        return [];
    }
}
