<?php

declare(strict_types=1);

namespace app\components;

use app\components\yii\MessageSource;
use app\models\db\Consultation;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;
use yii\i18n\I18N;

class StaticResourceTools
{
    private const INVALIDATE_MAX_HOURS = 2;

    /** @var string[] */
    private static array $foundModules = [];

    public static function detectAndRegisterModules(string $content): string
    {
        return preg_replace_callback("/\/(npm|js)[^\"']+/siu", static function ($matches) {
            self::$foundModules[] = trim($matches[0], "/");

            return $matches[0];
        }, $content);
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
        if (YII_DEBUG && isset($integrityMap['npm/vue.runtime.esm-browser.prod.js'])) {
            $integrityMap['npm/vue.runtime.esm-browser.js'] = $dependencies["integrity"]['npm/vue.runtime.esm-browser.js'];
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

            // Somewhat dirty hack in order to use the YII dev-build when YII_DEBUG is set
            // By mapping the .prod.js in the imports to the non-prod .js-file.
            if (YII_DEBUG) {
                if ($fileName === 'npm/vue.runtime.esm-browser.js') {
                    continue;
                }
                if ($fileName === 'npm/vue.runtime.esm-browser.prod.js') {
                    // Hint: Also replaced in detectAndRegisterModules
                    $path = $app->resourceBase . 'npm/vue.runtime.esm-browser.js';
                    $fileHash = $map['npm/vue.runtime.esm-browser.js'];

                    // @TODO Check if this will affect resources in subdirectories on the CDN
                    $imports[$app->resourceBase . 'npm/vue.runtime.esm-browser.prod.js'] = $path;
                }
            }

            // For local files, let's add a cache buster if the file was changed recently
            if ($localAssets) {
                $mtime = filemtime(__DIR__ . '/../web'. $path);
                $lastModified = time() - $mtime;
                if ($lastModified < self::INVALIDATE_MAX_HOURS * 3600) {
                    $path .= '?ts=' . $mtime;
                }
            }

            $imports['/' . $fileName] = $path;
            if (!$localAssets) {
                $cdnUrlBase = parse_url($app->resourceBase, PHP_URL_HOST);
                if (isset($cdnUrlBase['scheme'], $cdnUrlBase['host'], $cdnUrlBase['path']) && $cdnUrlBase['path'] !== '/') {
                    // JS-files that are hosted on the CDN and make use of absolute imports (starting with "/")
                    // are pointing to the root directory of the CDN - not the root directory of the Antragsgrün host.
                    // So with a resourceBase of https://cdn.motion.tools/v4.17.0/ , an "import ... from /js/test.js"
                    // would point to https://cdn.motion.tools/js/test.js , not /v4.17.0/js/test.js . We fix that here.

                    // It could be an option to change the imports in the CDN - however it would add complexity,
                    // as the integrity-hash of the importing scripts would change based on the subdirectory of the hash.
                    $imports[$cdnUrlBase['scheme'] . '://' . $cdnUrlBase['host'] . '/' . $fileName] = $path;
                }
            }
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

    public static function resourceUrl(string $url): string
    {
        $resourceBase = AntragsgruenApp::getInstance()->resourceBase;
        $localAssets = str_starts_with($resourceBase, '/');

        if ($localAssets) {
            $absolute = \Yii::$app->basePath . '/web/' . str_replace('/', DIRECTORY_SEPARATOR, $url);

            $mtime    = (file_exists($absolute) ? filemtime($absolute) : 0);
            $age      = time() - $mtime;
            if ($age < 604800) { // 1 Week
                $url .= (str_contains($url, '?') ? '&ts=' : '?ts=');
                $url .= $mtime;
            }
        }

        $newUrl = $resourceBase . $url;

        return Html::encode($newUrl);
    }
}
