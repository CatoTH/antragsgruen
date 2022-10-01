<?php

declare(strict_types=1);

namespace app\models\http;

use app\models\settings\Layout;
use yii\helpers\Url;
use yii\web\Response;

class RedirectResponse implements ResponseInterface
{
    private string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function renderYii(Layout $layoutParams, Response $response): ?string
    {
        $response->redirect(Url::to($this->url), 302);

        return null;
    }
}
