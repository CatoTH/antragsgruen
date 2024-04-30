<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\settings\Stylesheet;
use Tests\Support\Helper\TestBase;

class CssCompilerTest extends TestBase
{
    public function testCss(): void
    {
        $stylesheetSettings = new Stylesheet(Stylesheet::DEFAULTS_CLASSIC);
        ob_start();
        require(__DIR__ . '/../../views/pages/css.php');
        $css = ob_get_clean();

        $this->assertStringNotContainsString('@if', $css);
        $this->assertStringContainsString('.nav-tabs.nav-justified{border-bottom:0}', $css);
    }
}
