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

    public static function getJsModulesImportMap(): string
    {
        $cache = HashedStaticCache::getInstance('getJsModulesImportMap', []);
        if (YII_DEBUG) {
            $cache->setSkipCache(true);
        }
        $cache->setTimeout(self::MAP_CACHE_SECONDS);

        return $cache->getCached(function() {
            $app = AntragsgruenApp::getInstance();
            $publicPathBase = $app->resourceBase . 'js/';

            $map = [];
            $finder = new Finder();
            $finder->files()->in([self::JS_PATH, self::VUE_PATH])->name('*.js');
            foreach ($finder as $file) {
                $lastModified = time() - $file->getMTime();
                if ($lastModified > self::INVALIDATE_MAX_HOURS * 3600) {
                    continue;
                }

                $path = basename($file->getPath());
                if ($path === "vue") {
                    $publicUrl = $publicPathBase . $path . '/' . $file->getFilename();
                } else {
                    $publicUrl = $publicPathBase . "modules/" . $path . '/' . $file->getFilename();
                }
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

    public static function getTranslations(Consultation $consultation, string $category): array
    {
        /** @var I18N $i18n */
        $i18n = \Yii::$app->get('i18n');
        /** @var MessageSource $messagesource */
        $messagesource = $i18n->getMessageSource($category);
        return $messagesource->loadMessages($category, $consultation->wordingBase, true);
    }
}
