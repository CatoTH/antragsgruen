<?php

declare(strict_types=1);

namespace app\commands;

use app\components\LiveTools;
use yii\console\Controller;

class LiveController extends Controller
{
    /**
     * Sends a test message to a user
     */
    public function actionSendUserMessage(string $site, string $consultation, int $userId, string $message): void
    {
        $routingKey = 'user.' . $site . '.' . $consultation . '.' . $userId;

        LiveTools::sendToRabbitMq($routingKey, (string)json_encode(['username' => $message]));
    }
}
