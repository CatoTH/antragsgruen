<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\yii\{UrlManager, UrlNormalizer};
use Tests\Support\Helper\TestBase;
use Yii;
use yii\web\{Request, UrlNormalizerRedirectException};

class UrlNormalizerTest extends TestBase
{
    private ?string $originalMethod = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalMethod = $_SERVER['REQUEST_METHOD'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->originalMethod;
        }
        parent::tearDown();
    }

    /**
     * Parses the request with path-based multisite rules like those in config/urls.php.
     * Returns the redirect target, or null if the request is routed without a redirect.
     */
    private function getRedirect(string $method, string $requestUri, string $baseUrl = ''): ?string
    {
        $_SERVER['REQUEST_METHOD'] = $method;

        $path = parse_url($requestUri, PHP_URL_PATH);
        $request = new Request();
        $request->setUrl($requestUri);
        $request->setBaseUrl($baseUrl);
        $request->setPathInfo(ltrim(urldecode(substr($path, strlen($baseUrl))), '/'));
        Yii::$app->set('request', $request);

        $manager = new UrlManager([
            'enablePrettyUrl' => true,
            'showScriptName'  => false,
            'cache'           => null,
            'normalizer'      => UrlNormalizer::class,
            'rules'           => [
                '/<subdomain:[\w_-]+>/'                                  => 'consultation/home',
                '/<subdomain:[\w_-]+>/api-docs/<action:\w*>'             => 'swagger_ui/swaggerui/index', // as in plugins/swagger_ui
                '/<subdomain:[\w_-]+>/<consultationPath:[\w_-]+>/'       => 'consultation/index',
                '/<subdomain:[\w_-]+>/<consultationPath:[\w_-]+>/admin/' => 'admin/index',
            ],
        ]);

        try {
            $manager->parseRequest($request);
        } catch (UrlNormalizerRedirectException $e) {
            $this->assertSame(302, $e->statusCode);
            return $e->url;
        }

        return null;
    }

    public function testRedirectsGetAndHead(): void
    {
        $this->assertSame('/site', $this->getRedirect('GET', '/site/'));
        $this->assertSame('/site/consultation', $this->getRedirect('GET', '/site/consultation/'));
        $this->assertSame('/site/consultation/admin', $this->getRedirect('HEAD', '/site/consultation/admin/'));
    }

    public function testRedirectsUnmatchedPaths(): void
    {
        $this->assertSame('/site/consultation/unknown/page', $this->getRedirect('GET', '/site/consultation/unknown/page/'));
    }

    public function testKeepsQueryStringAndEncoding(): void
    {
        $this->assertSame('/site/consultation?a=1&b=%20x', $this->getRedirect('GET', '/site/consultation/?a=1&b=%20x'));
        $this->assertSame('/site/T%C3%A4st', $this->getRedirect('GET', '/site/T%C3%A4st/'));
    }

    public function testKeepsSubdirectory(): void
    {
        $this->assertSame('/antragsgruen/web/site/consultation', $this->getRedirect('GET', '/antragsgruen/web/site/consultation/', '/antragsgruen/web'));
    }

    public function testNoRedirectIfRuleAcceptsTrailingSlash(): void
    {
        $this->assertNull($this->getRedirect('GET', '/site/api-docs/'));
        $this->assertSame('/site/api-docs/index', $this->getRedirect('GET', '/site/api-docs/index/'));
    }

    public function testNoRedirectForOtherMethods(): void
    {
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->assertNull($this->getRedirect($method, '/site/consultation/'), $method);
        }
    }

    public function testNoRedirectWithoutTrailingSlash(): void
    {
        $this->assertNull($this->getRedirect('GET', '/site/consultation'));
        $this->assertNull($this->getRedirect('GET', '/site/consultation?a=b/'));
        $this->assertNull($this->getRedirect('GET', '/'));
        $this->assertNull($this->getRedirect('GET', '/antragsgruen/web/', '/antragsgruen/web'));
    }

    public function testLocationWithoutTrailingSlash(): void
    {
        $this->assertSame('/site', UrlNormalizer::getLocationWithoutTrailingSlash('/site///'));
        $this->assertSame('/site?x=/', UrlNormalizer::getLocationWithoutTrailingSlash('/site/?x=/'));
        $this->assertNull(UrlNormalizer::getLocationWithoutTrailingSlash('/'));
        $this->assertNull(UrlNormalizer::getLocationWithoutTrailingSlash('/site%2F'));
    }

    public function testNoRedirectToOtherHosts(): void
    {
        $this->assertNull(UrlNormalizer::getLocationWithoutTrailingSlash('//example.org/'));
        $this->assertNull(UrlNormalizer::getLocationWithoutTrailingSlash('/\\example.org/'));
        $this->assertNull(UrlNormalizer::getLocationWithoutTrailingSlash('https://example.org/site/'));
    }
}
