<?php

declare(strict_types=1);

namespace app\models\http;

use app\models\settings\Layout;
use yii\web\Response;

class RestApiResponse implements ResponseInterface
{
    private int $statusCode;
    private ?string $rawJson;
    private ?array $data;

    public function __construct(int $statusCode, ?array $data, ?string $rawJson = null)
    {
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->rawJson = $rawJson;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getJson(): string
    {
        if ($this->rawJson) {
            return $this->rawJson;
        } else {
            return json_encode($this->data, JSON_THROW_ON_ERROR);
        }
    }

    public function renderYii(Layout $layoutParams, Response $response): ?string
    {
        $layoutParams->setFallbackLayoutIfNotInitializedYet();
        $layoutParams->robotsNoindex = true;
        $response->format = Response::FORMAT_RAW;
        $response->headers->add('Content-Type', 'application/json');
        $response->statusCode = $this->statusCode;
        $response->content = $this->getJson();
        \Yii::$app->end();

        return null;
    }
}
