<?php

declare(strict_types=1);

namespace app\components;

use app\models\settings\AntragsgruenApp;

class HashedStaticFileCache
{
    private static function hashDependencies(?array $dep): string
    {
        return md5(print_r($dep, true));
    }

    private static function getDirectory(string $key): string {
        return AntragsgruenApp::getInstance()->viewCacheFilePath . '/' . substr($key, 0, 2);
    }

    public static function getCache(string $function, ?array $dependencies): ?string
    {
        if (!AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $cached = HashedStaticCache::getCache($function, $dependencies);
            return (is_string($cached) ? $cached : null);
        }
        $key = md5($function . self::hashDependencies($dependencies));
        $directory = self::getDirectory($key);
        if (!file_exists($directory)) {
            return null;
        }

        if (file_exists($directory . '/' . $key)) {
            return (string)file_get_contents($directory . '/' . $key);
        } else {
            return null;
        }
    }

    public static function setCache(string $function, ?array $dependencies, string $data): void
    {
        if (!AntragsgruenApp::getInstance()->viewCacheFilePath) {
            HashedStaticCache::setCache($function, $dependencies, $data);
            return;
        }

        $key = md5($function . self::hashDependencies($dependencies));
        $directory = self::getDirectory($key);
        if (!file_exists($directory)) {
            mkdir($directory, 0700);
        }

        file_put_contents($directory . '/' . $key, $data);
    }

    public static function flushCache(string $function, ?array $dependencies): void
    {
        if (!AntragsgruenApp::getInstance()->viewCacheFilePath) {
            HashedStaticCache::flushCache($function, $dependencies);
            return;
        }

        $key = md5($function . self::hashDependencies($dependencies));
        $directory = self::getDirectory($key);
        if (file_exists($directory . '/' . $key)) {
            unlink($directory . '/' . $key);
        }
    }
}
