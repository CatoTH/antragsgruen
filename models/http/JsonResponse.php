<?php

declare(strict_types=1);

namespace app\models\http;

use app\models\settings\Layout;
use yii\web\Response;

class JsonResponse implements ResponseInterface
{
    private array $json;

    public function __construct(array $json)
    {
        $this->json = $json;
    }

    public function renderYii(Layout $layoutParams, Response $response): ?string
    {
        $response->format = Response::FORMAT_RAW;
        $response->headers->add('Content-Type', 'application/json');

        return json_encode($this->json, JSON_THROW_ON_ERROR);
    }
}
