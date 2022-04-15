<?php

namespace app\components;

class HashedStaticCache
{
    /**
     * @param mixed $dep
     */
    private static function hashDependencies($dep): string
    {
        return md5(print_r($dep, true));
    }

    /**
     * @param mixed $dependencies
     *
     * @return mixed|false
     */
    public static function getCache(string $function, $dependencies)
    {
        if (YII_ENV === 'test') {
            return false;
        }
        $key = md5($function . self::hashDependencies($dependencies));

        return \Yii::$app->cache->get($key);
    }

    /**
     * @param mixed $dependencies
     * @param mixed $data
     */
    public static function setCache(string $function, $dependencies, $data): void
    {
        if (YII_ENV === 'test') {
            return;
        }
        $key = md5($function . self::hashDependencies($dependencies));
        \Yii::$app->cache->set($key, $data);
    }

    /**
     * @param mixed $dependencies
     */
    public static function flushCache(string $function, $dependencies)
    {
        if (YII_ENV === 'test') {
            return;
        }
        $key = md5($function . self::hashDependencies($dependencies));
        \Yii::$app->cache->delete($key);
    }
}
