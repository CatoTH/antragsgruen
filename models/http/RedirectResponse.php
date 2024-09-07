<?php

declare(strict_types=1);

namespace app\models\http;

use app\models\settings\{AntragsgruenApp, Layout};
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
        $this->url = $this->sanitizeRedirect($url);
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

    private function sanitizeRedirect(string $url): string
    {
        $filtered = filter_var($url, FILTER_SANITIZE_URL);
        if (!$filtered) {
            return '/';
        }
        $app = AntragsgruenApp::getInstance();
        if ($app->domainSubdomain && $this->urlStartsWithVariableHost($filtered, $app->domainSubdomain)) {
            return $filtered;
        }
        if ($app->domainPlain && $this->urlStartsWithVariableHost($filtered, $app->domainPlain)) {
            return $filtered;
        }
        if (preg_match('/^\/[^\/]/', $filtered)) { // Starts with slash, followed by other character (we're not using // and /// urls)
            return $filtered;
        }
        return '/'; // Fallback
    }

    private function urlStartsWithVariableHost(string $url, string $host): bool
    {
        $hostRegexp = str_replace('<subdomain:[\w_-]+>', '[\w_-]+', $host);
        $hostRegexp = str_replace('.', '\.', $hostRegexp);
        $hostRegexp = str_replace('/', '\/', $hostRegexp);

        return (bool) preg_match('/^' . $hostRegexp . '/', $url);
    }
}
