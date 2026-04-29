<?php

declare(strict_types=1);

namespace app\components;

use app\components\yii\MessageSource;
use app\models\db\Consultation;
use app\models\settings\AntragsgruenApp;
use Symfony\Component\Finder\Finder;
use yii\i18n\I18N;

class JsTools
{
    private const JS_PATH = __DIR__ . '/../web/js/modules/';
    private const VUE_PATH = __DIR__ . '/../web/js/vue/';
    private const INVALIDATE_MAX_HOURS = 2;
    private const MAP_CACHE_SECONDS = 60;

    private static array $foundModules = [];

    public static function detectAndRegisterModules(string $content): string
    {
        return preg_replace_callback("/\/(npm|js)[^\"']+/siu", static function ($matches) {
            self::$foundModules[] = $matches[0];
            // @TODO replace paths
            return $matches[0];
        }, $content);
    }

    public static function getJsModulesImportMap(): string
    {
        // @TODO Only show relevant modules, CDN support
        $cache = HashedStaticCache::getInstance('getJsModulesImportMap', []);
        if (YII_DEBUG) {
            $cache->setSkipCache(true);
        }
        $cache->setTimeout(self::MAP_CACHE_SECONDS);

        return $cache->getCached(function() {
            $app = AntragsgruenApp::getInstance();
            $basePathBase = '/js/';
            $publicPathBase = $app->resourceBase . 'js/';
            $nonRootResourcePath = ($app->resourceBase !== '/');

            $map = [];
            if (YII_DEBUG) {
                $map['/npm/vue.runtime.esm-browser.prod.js'] = $app->resourceBase . 'npm/vue.runtime.esm-browser.js';
            } elseif ($nonRootResourcePath) {
                $map['/npm/vue.runtime.esm-browser.prod.js'] = $app->resourceBase . 'npm/vue.runtime.esm-browser.prod.js';
            }

            $finder = new Finder();
            $finder->files()->in([self::JS_PATH, self::VUE_PATH])->name('*.js');
            foreach ($finder as $file) {
                $lastModified = time() - $file->getMTime();
                if ($lastModified > self::INVALIDATE_MAX_HOURS * 3600 && !$nonRootResourcePath) {
                    continue;
                }

                $url = explode($basePathBase, $file->getPath())[1] . '/' . $file->getFilename();
                $map[$basePathBase . $url] = $publicPathBase . $url . '?ts=' . $file->getMTime();
            }

            if (count($map) === 0) {
                return '';
            }

            return '<script type="importmap">{
                "imports": ' . json_encode($map) . '
            }</script>';
        });
    }

    public static function getTranslations(?Consultation $consultation, string $category): array
    {
        /** @var I18N $i18n */
        $i18n = \Yii::$app->get('i18n');
        /** @var MessageSource $messagesource */
        $messagesource = $i18n->getMessageSource($category);

        if ($consultation) {
            $language = $consultation->wordingBase;
        } else {
            $language = AntragsgruenApp::getInstance()->baseLanguage;
        }

        return $messagesource->loadJsMessages($category, $language);
    }
}
