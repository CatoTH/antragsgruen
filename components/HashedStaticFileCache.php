<?php

namespace app\components;

use app\models\settings\AntragsgruenApp;

class HashedStaticFileCache
{
    /**
     * @param mixed $dep
     */
    private static function hashDependencies($dep): string
    {
        return md5(print_r($dep, true));
    }

    private static function getDirectory(string $key): string {
        return AntragsgruenApp::getInstance()->viewCacheFilePath . '/' . substr($key, 0, 2);
    }

    /**
     * @param mixed $dependencies
     */
    public static function getCache(string $function, $dependencies): ?string
    {
        if (!AntragsgruenApp::getInstance()->viewCacheFilePath) {
            return HashedStaticCache::getCache($function, $dependencies);
        }
        $key = md5($function . static::hashDependencies($dependencies));
        $directory = static::getDirectory($key);
        if (!file_exists($directory)) {
            return null;
        }

        if (file_exists($directory . '/' . $key)) {
            return file_get_contents($directory . '/' . $key);
        } else {
            return null;
        }
    }

    /**
     * @param mixed $dependencies
     */
    public static function setCache(string $function, $dependencies, string $data): void
    {
        if (!AntragsgruenApp::getInstance()->viewCacheFilePath) {
            HashedStaticCache::setCache($function, $dependencies, $data);
            return;
        }

        $key = md5($function . static::hashDependencies($dependencies));
        $directory = static::getDirectory($key);
        if (!file_exists($directory)) {
            mkdir($directory, 0700);
        }

        file_put_contents($directory . '/' . $key, $data);
    }

    /**
     * @param mixed $dependencies
     */
    public static function flushCache(string $function, $dependencies)
    {
        if (!AntragsgruenApp::getInstance()->viewCacheFilePath) {
            HashedStaticCache::flushCache($function, $dependencies);
            return;
        }

        $key = md5($function . static::hashDependencies($dependencies));
        $directory = static::getDirectory($key);
        if (file_exists($directory . '/' . $key)) {
            unlink($directory . '/' . $key);
        }
    }
}
