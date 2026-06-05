<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\CookieUser;
use app\components\LiveTools;
use app\models\settings\AntragsgruenApp;
use app\models\api\{SpeechUser, SpeechQueue as SpeechQueueApi};
use app\models\http\{RestApiExceptionResponse, RestApiResponse};
use app\models\settings\Privileges;
use app\views\speech\LayoutHelper;
use app\models\db\{SpeechQueue, SpeechQueueItem, User};
use yii\web\Controller;

class ErrorTrackingController extends Base
{
    public function actionJs(): RestApiResponse
    {
        $app = AntragsgruenApp::getInstance();
        if ($app->jsErrorTracking === null) {
            return new RestApiResponse(400, null, '{"success": false, "error": "disabled"}');
        }

        $parts = parse_url($app->jsErrorTracking);
        if (!isset($parts['scheme']) && isset($parts['path'])) {
            $parts['scheme'] = 'file';
        }
        if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['file', 'otel'])) {
            return new RestApiResponse(500, null, '{"success": true, "error": "error tracking not set up correctly"}');
        }

        $raw = $this->getPostBody();
        if (!$raw) {
            return new RestApiResponse(400, null, '{"success": false, "error": "no content"}');
        }

        $data = json_decode($raw, true);
        if (!$data || json_last_error() !== JSON_ERROR_NONE) {
            return new RestApiResponse(400, null, '{"success": false, "error": "could not parse content"}');
        }

        if ($parts['scheme'] == 'file') {
            if (!isset($parts['path'])) {
                return new RestApiResponse(500, null, '{"success": true, "error": "error tracking not set up correctly"}');
            }
            if (!is_writable($parts['path'])) {
                return new RestApiResponse(500, null, '{"success": true, "error": "error log not writable"}');
            }
            file_put_contents($app->jsErrorTracking, $raw . "\n", FILE_APPEND);
        }
        if ($parts['scheme'] == 'otel') {
            // @TODO
        }

        return new RestApiResponse(200, null, '{"success": true}');
    }
}
