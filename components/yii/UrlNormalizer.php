<?php

declare(strict_types=1);

namespace app\components\yii;

use app\components\RequestContext;
use yii\web\{Application, UrlNormalizerRedirectException};

/**
 * Redirects URLs with a trailing slash ("/site/consultation/") to the URL without it, independent of the web server.
 *
 * - Only GET and HEAD requests are redirected. Other methods are routed exactly as before,
 *   as redirecting them would turn e.g. a POST into a GET and drop the body.
 * - The target is derived from the raw request URI rather than re-created from the route, so it keeps
 *   the installation subdirectory, the original URL encoding and the query string as they were.
 */
class UrlNormalizer extends \yii\web\UrlNormalizer
{
    public $action = self::ACTION_REDIRECT_TEMPORARY;

    // Only trailing slashes are in scope; web servers already merge duplicate slashes by default.
    public $collapseSlashes = false;

    /**
     * @param string $pathInfo
     * @param string $suffix
     * @param bool $normalized
     */
    public function normalizePathInfo($pathInfo, $suffix, &$normalized = false): string
    {
        if (!$this->isRedirectableRequest()) {
            $normalized = false;
            return $pathInfo;
        }

        return parent::normalizePathInfo($pathInfo, $suffix, $normalized);
    }

    /**
     * @param array $route
     */
    public function normalizeRoute($route): array
    {
        if ($this->action !== self::ACTION_REDIRECT_PERMANENT && $this->action !== self::ACTION_REDIRECT_TEMPORARY) {
            return parent::normalizeRoute($route);
        }

        $location = self::getLocationWithoutTrailingSlash(RequestContext::getCurrentUrl());
        if ($location === null) {
            // Nothing safe to redirect to: serve the normalized route directly
            return $route;
        }

        throw new UrlNormalizerRedirectException($location, $this->action);
    }

    /**
     * Returns the given request URI (path and query) without trailing slashes in the path,
     * or null if that does not lead to a different, same-site URL.
     */
    public static function getLocationWithoutTrailingSlash(string $requestUri): ?string
    {
        $queryPos = strpos($requestUri, '?');
        $path = ($queryPos === false ? $requestUri : substr($requestUri, 0, $queryPos));
        $query = ($queryPos === false ? '' : substr($requestUri, $queryPos));

        $newPath = rtrim($path, '/');

        // "/" itself, or an encoded slash like "/site%2F" that would redirect to itself
        if ($newPath === '' || $newPath === $path) {
            return null;
        }
        // "//example.org/" would become the protocol-relative URL "//example.org", leading to another host
        if (!str_starts_with($newPath, '/') || str_starts_with($newPath, '//') || str_starts_with($newPath, '/\\')) {
            return null;
        }

        return $newPath . $query;
    }

    private function isRedirectableRequest(): bool
    {
        $app = \Yii::$app;

        return $app instanceof Application && in_array($app->getRequest()->getMethod(), ['GET', 'HEAD'], true);
    }
}
