<?php

declare(strict_types=1);

namespace app\plugins\joulefish_simplify;

use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\settings\Layout;
use app\plugins\ModuleBase;
use Yii;

class Module extends ModuleBase
{
    public function init(): void
    {
        parent::init();
    }

    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation): array
    {
        return [
            new LayoutHooks($layoutSettings, $consultation),
        ];
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        return array_merge(
            [
                $dom . 'joulefish_simplify/simplify/process' => 'joulefish_simplify/simplify/process',
            ],
            parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld)
        );
    }

    /**
     * Get configured API endpoint URL
     */
    public static function getApiUrl(): string
    {
        $env = getenv('JOULEFISH_API_URL') ?: ($_ENV['JOULEFISH_API_URL'] ?? null);
        if ($env && is_string($env) && trim($env) !== '') {
            return trim($env);
        }

        $configFile = __DIR__ . '/config.json';
        if (is_file($configFile)) {
            $data = json_decode((string)file_get_contents($configFile), true);
            if (is_array($data) && !empty($data['apiUrl'])) {
                return trim($data['apiUrl']);
            }
        }

        return 'https://simplify-joulefish.fn.deploybase.eu';
    }

    /**
     * Get configured API Key
     */
    public static function getApiKey(): string
    {
        $env = getenv('JOULEFISH_API_KEY') ?: ($_ENV['JOULEFISH_API_KEY'] ?? null);
        if ($env && is_string($env) && trim($env) !== '') {
            return trim($env);
        }

        $configFile = __DIR__ . '/config.json';
        if (is_file($configFile)) {
            $data = json_decode((string)file_get_contents($configFile), true);
            if (is_array($data) && !empty($data['apiKey'])) {
                return trim($data['apiKey']);
            }
        }

        return '';
    }

    /**
     * Render widget into the motion view template hook
     */
    public static function renderMotionWidget(Motion $motion): string
    {
        return Yii::$app->view->render('@app/plugins/joulefish_simplify/views/motion_hook', [
            'motion' => $motion,
            'apiUrl' => self::getApiUrl(),
        ]);
    }
}

