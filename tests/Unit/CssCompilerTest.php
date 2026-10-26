<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\db\ConsultationFile;
use app\models\settings\{AntragsgruenApp, Stylesheet};
use Tests\Support\Helper\TestBase;

class CssCompilerTest extends TestBase
{
    private function renderCss(array $settings): string
    {
        $stylesheetSettings = new Stylesheet($settings);
        ob_start();
        require(__DIR__ . '/../../views/pages/css.php');

        return (string)ob_get_clean();
    }

    public function testCss(): void
    {
        $css = $this->renderCss(Stylesheet::DEFAULTS_CLASSIC);

        $this->assertStringNotContainsString('@if', $css);
        $this->assertStringContainsString('.nav-tabs.nav-justified{border-bottom:0}', $css);
    }

    public static function resourceBaseProvider(): array
    {
        return [
            ['/', '/'],
            ['/antragsgruen/web/', '/antragsgruen/web/'],
            ['/antragsgruen/web', '/antragsgruen/web/'],
            ['https://cdn.example.org/antragsgruen/', 'https://cdn.example.org/antragsgruen/'],
        ];
    }

    /**
     * @dataProvider resourceBaseProvider
     */
    public function testUrlsAreAbsolute(string $resourceBase, string $expectedPrefix): void
    {
        AntragsgruenApp::getInstance()->resourceBase = $resourceBase;
        $settings = Stylesheet::DEFAULTS_CLASSIC;
        unset($settings['backgroundImage']); // to use the default background images
        $css = $this->renderCss($settings);

        foreach ([
            'fonts/glyphicons-halflings-regular.woff2',
            'fonts/fontello.woff2',
            'fonts/OpenSans/fonts/',
            'fonts/BitstreamVeraSansMono/',
            'fonts/firasans/',
            'img/wallpaper.jpg',
            'img/backgroundGradient.png',
            'img/logo.png',
        ] as $path) {
            $this->assertStringContainsString('url("' . $expectedPrefix . $path, $css);
        }
        $this->assertDoesNotMatchRegularExpression('/url\(["\']?\.\//', $css);
    }

    public function testCacheTagDependsOnResourceBase(): void
    {
        AntragsgruenApp::getInstance()->resourceBase = '/';
        $rootTag = ConsultationFile::getStylesheetCacheTag();
        $this->assertStringStartsWith(ANTRAGSGRUEN_VERSION . ' ', $rootTag);

        AntragsgruenApp::getInstance()->resourceBase = '/antragsgruen/web/';
        $this->assertNotSame($rootTag, ConsultationFile::getStylesheetCacheTag());
    }
}
