<?php

declare(strict_types=1);

namespace app\components;

use app\models\settings\AntragsgruenApp;
use Symfony\Component\Finder\Finder;

class JsModuleImportMap
{
    private const JS_PATH = __DIR__ . '/../web/js/modules/';
    private const INVALIDATE_MAX_HOURS = 2;
    private const MAP_CACHE_SECONDS = 60;

    public static function getJsModulesImportMap(): string
    {
        $cache = HashedStaticCache::getInstance('getJsModulesImportMap', []);
        if (YII_DEBUG) {
            $cache->setSkipCache(true);
        }
        $cache->setTimeout(self::MAP_CACHE_SECONDS);

        return $cache->getCached(function() {
            $app = AntragsgruenApp::getInstance();
            $publicPathBase = $publicPath = $app->resourceBase . 'js/modules/';

            $map = [];
            $finder = new Finder();
            $finder->files()->in(self::JS_PATH)->name('*.js');
            foreach ($finder as $file) {
                $lastModified = time() - $file->getMTime();
                if ($lastModified > self::INVALIDATE_MAX_HOURS * 3600) {
                    continue;
                }

                $path = basename($file->getPath());
                $publicUrl = $publicPathBase . $path . '/' . $file->getFilename();
                $map[$publicUrl] = $publicUrl . '?ts=' . $file->getMTime();
            }

            if (count($map) === 0) {
                return '';
            }

            return '<script type="importmap">{
                "imports": ' . json_encode($map) . '
            }</script>';
        });
    }
}
