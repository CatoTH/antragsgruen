<?php

declare(strict_types=1);

namespace app\models\http;

use app\models\settings\Layout;
use yii\web\Response;

class HtmlResponse implements ResponseInterface
{
    private string $html;

    public function __construct(string $html)
    {
        $this->html = $html;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function renderYii(Layout $layoutParams, Response $response): ?string
    {
        return $this->html;
    }
}
