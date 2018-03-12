<?php

namespace app\plugins;

use yii\base\Module;

class ModuleBase extends Module
{
    /**
     */
    public function init()
    {
        parent::init();

        if (\Yii::$app instanceof \yii\console\Application) {
            $ref = new \ReflectionClass($this);
            $this->controllerNamespace = $ref->getNamespaceName() . '\\commands';
        }
    }

    /**
     */
    public static function getMotionUrlRoutes()
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
            $urls[$dommotion . '/' . $url] = $route;
            $urls[$dommotionOld . '/' . $url] = $route;
        }
        return $urls;
    }
}
