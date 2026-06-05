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

        if ($parts['scheme'] == 'file') {

        } elseif ($parts['scheme'] == 'otel') {
            return new RestApiResponse(500, null, '{"success": true, "error": "server error"}');
        }

        return new RestApiResponse(200, null, '{"success": true}');
    }
}
