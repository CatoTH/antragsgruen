<?php

declare(strict_types=1);

namespace app\models\http;

use app\models\settings\Layout;
use yii\web\Response;

class HtmlErrorResponse implements ResponseInterface
{
    private string $message;
    private int $statusCode;

    public function __construct(int $statusCode, string $message)
    {
        $this->statusCode = $statusCode;
        $this->message = $message;
    }

    public function renderYii(Layout $layoutParams, Response $response): ?string
    {
        $layoutParams->setFallbackLayoutIfNotInitializedYet();
        $layoutParams->robotsNoindex = true;
        $response->statusCode = $this->statusCode;
        $response->content = \Yii::$app->controller->render(
            '@app/views/errors/error',
            [
                'httpStatus' => $this->statusCode,
                'message' => $this->message,
                'name' => 'Error',
            ]
        );
        \Yii::$app->end();

        return null;
    }
}
