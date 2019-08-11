<?php

namespace app\components;

class HashedStaticCache
{
    /**
     * @param mixed $dep
     *
     * @return string
     */
    private static function hashDependencies($dep)
    {
        return md5(print_r($dep, true));
    }

    /**
     * @param string $function
     * @param mixed $dependencies
     *
     * @return mixed|false
     */
    public static function getCache($function, $dependencies)
    {
        if (YII_ENV === 'test') {
            return false;
        }
        $key = md5($function . static::hashDependencies($dependencies));

        return \Yii::$app->cache->get($key);
    }

    /**
     * @param string $function
     * @param mixed $dependencies
     * @param mixed $data
     */
    public static function setCache($function, $dependencies, $data)
    {
        if (YII_ENV === 'test') {
            return;
        }
        $key = md5($function . static::hashDependencies($dependencies));
        \Yii::$app->cache->set($key, $data);
    }

    /**
     * @param string $function
     * @param mixed $dependencies
     */
    public static function flushCache($function, $dependencies)
    {
        if (YII_ENV === 'test') {
            return;
        }
        $key = md5($function . static::hashDependencies($dependencies));
        \Yii::$app->cache->delete($key);
    }
}
