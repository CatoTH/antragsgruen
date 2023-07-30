<?php

declare(strict_types=1);

namespace app\components;

class HashedStaticCache
{
    private static function hashDependencies(?array $dep): string
    {
        return md5(print_r($dep, true));
    }

    /**
     * @return mixed|false
     */
    public static function getCache(string $function, ?array $dependencies): mixed
    {
        if (YII_ENV === 'test') {
            return false;
        }
        $key = md5($function . self::hashDependencies($dependencies));

        return \Yii::$app->cache->get($key);
    }

    public static function setCache(string $function, ?array $dependencies, mixed $data, ?int $duration = null): void
    {
        if (YII_ENV === 'test') {
            return;
        }
        $key = md5($function . self::hashDependencies($dependencies));
        \Yii::$app->cache->set($key, $data, $duration);
    }

    public static function flushCache(string $function, ?array $dependencies): void
    {
        if (YII_ENV === 'test') {
            return;
        }
        $key = md5($function . self::hashDependencies($dependencies));
        \Yii::$app->cache->delete($key);
    }
}
