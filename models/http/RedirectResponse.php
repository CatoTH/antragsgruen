<?php

declare(strict_types=1);

namespace app\models\http;

use app\models\settings\Layout;
use yii\helpers\Url;
use yii\web\Response;

class RedirectResponse implements ResponseInterface
{
    public const REDIRECT_PERMANENT = 301;
    public const REDIRECT_FOUND = 302;
    public const REDIRECT_TEMPORARY = 307;

    private string $url;
    private int $status;

    public function __construct(string $url, int $status = 302)
    {
        $this->url = $url;
        $this->status = $status;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function renderYii(Layout $layoutParams, Response $response): ?string
    {
        $response->redirect(Url::to($this->url), $this->status);

        return null;
    }
}
