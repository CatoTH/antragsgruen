<?php

declare(strict_types=1);

namespace app\plugins\egp_usersync;

use app\components\UrlHelper;
use app\models\db\{AmendmentSupporter, Consultation, Motion, MotionSupporter, Site};
use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\exceptions\ConfigurationError;
use app\models\http\RedirectResponse;
use app\models\http\ResponseInterface;
use app\models\settings\IMotionStatus;
use app\plugins\egp\pdf\Egp;
use app\plugins\ModuleBase;
use yii\base\Event;
use yii\web\View;

class Module extends ModuleBase
{
    public static string $webhookApiToken;

    public function init(): void
    {
        parent::init();

        if (!file_exists(__DIR__ . '/../../config/egp_usersync.json')) {
            throw new ConfigurationError('egp_usersync.json not found');
        }
        $json = json_decode((string)file_get_contents(__DIR__ . '/../../config/egp_usersync.json'), true);
        if (!isset($json['apiToken'])) {
            throw new ConfigurationError('apiToken not found in configuration');
        }
        self::$webhookApiToken = $json['apiToken'];
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        return array_merge(
            [
                $dom . 'webhook/usersync' => '/egp_usersync/usersync/usersync',
            ],
            parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld)
        );
    }
}
