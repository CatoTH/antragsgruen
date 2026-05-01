<?php

declare(strict_types=1);

namespace app\components;

use app\components\yii\MessageSource;
use app\models\db\Consultation;
use app\models\settings\AntragsgruenApp;
use yii\i18n\I18N;

class JsTools
{
    private const INVALIDATE_MAX_HOURS = 2;

    /** @var string[] */
    private static array $foundModules = [];

    public static function detectAndRegisterModules(string $content): string
    {
        $app = AntragsgruenApp::getInstance();

        return preg_replace_callback("/\/(npm|js)[^\"']+/siu", static function ($matches) use ($app) {
            $file = trim($matches[0], "/");
            self::$foundModules[] = $file;

            return $app->resourceBase . $file;
        }, $content);
    }

    /**
     * @return array<string, string>
     */
    private static function getJsModulesImportBase(): array
    {
        $resourceBase = AntragsgruenApp::getInstance()->resourceBase;

        $map = [];
        if (YII_DEBUG) {
            $map['/npm/vue.runtime.esm-browser.prod.js'] = $resourceBase . 'npm/vue.runtime.esm-browser.js';
        } elseif ($resourceBase !== '/') {
            $map['/npm/vue.runtime.esm-browser.prod.js'] = $resourceBase . 'npm/vue.runtime.esm-browser.prod.js';
        }

        return $map;
    }

    /**
     * @param string[] $modules
     *
     * @return array<string, string>
     */
    private static function resolveDependentModules(array $modules): array
    {
        /**
         * @var array{
         *     dependencies: array<string, string[]>,
         *     integrity: array<string, string>,
         *     translations: array<string, string[][]>,
         * } $dependencies
         */
        $dependencies = json_decode((string) file_get_contents(__DIR__ . '/../assets/js-dependencies.json'), true, flags: JSON_THROW_ON_ERROR);

        do {
            $newModules = $modules;
            foreach ($modules as $module) {
                if (isset($dependencies["dependencies"][$module])) {
                    $newModules = array_merge($newModules, $dependencies["dependencies"][$module]);
                } else {
                    echo "No dependency declared for module: $module\n";
                }
            }

            $newModules = array_unique($newModules);
            $changed = count($modules) !== count($newModules);

            $modules = $newModules;
        } while ($changed);

        $integrityMap = [];
        foreach ($modules as $module) {
            if (isset($dependencies["integrity"][$module])) {
                $integrityMap[$module] = $dependencies["integrity"][$module];
            } else {
                echo "No integrity check for module: $module\n";
            }
        }

        return $integrityMap;
    }

    public static function getJsModulesImportMap(): string
    {
        $app = AntragsgruenApp::getInstance();

        $map = self::resolveDependentModules(self::$foundModules);

        if (count($map) === 0) {
            return '';
        }

        $imports = [];
        $integrity = [];

        $localAssets = str_starts_with($app->resourceBase, '/');
        foreach ($map as $fileName => $fileHash) {
            $path = $app->resourceBase . $fileName;

            // For local files, let's add a cache buster if the file was changed recently
            if ($localAssets) {
                $mtime = filemtime(__DIR__ . '/../web'. $path);
                $lastModified = time() - $mtime;
                if ($lastModified < self::INVALIDATE_MAX_HOURS * 3600) {
                    $path .= '?ts=' . $mtime;
                }
            }

            $imports['/' . $fileName] = $path;
            $integrity[$path] = $fileHash;
        }

        $importmap = [
            "imports" => $imports,
        ];
        if (!$localAssets) {
            // Only really relevant for CDNs; for local assets, it's probably too much as anyone who could
            // modify the assets, could also modify the hashes.
            $importmap["integrity"] = $integrity;
        }

        return '<script type="importmap">' . json_encode($importmap) . '</script>';
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
