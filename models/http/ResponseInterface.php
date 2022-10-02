<?php

declare(strict_types=1);

namespace app\models\http;

use app\models\settings\Layout;
use yii\web\Response;

/**
 * This helper interface serves two purposes:
 * - allowing typing in the controller methods
 * - easier future switching of Request/Response classes around the controller methods
 */
interface ResponseInterface
{
    public function renderYii(Layout $layoutParams, Response $response): ?string;
}
