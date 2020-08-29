<?php

declare(strict_types=1);

namespace unit;

use app\models\settings\Stylesheet;

class CssCompilerTest extends TestBase
{
    /** @noinspection PhpUnusedLocalVariableInspection */
    public function testCss() {
        $stylesheetSettings = new Stylesheet(Stylesheet::$DEFAULTS_CLASSIC);
        $format = \ScssPhp\ScssPhp\Formatter\Compact::class;
        ob_start();
        require(__DIR__ . '/../../views/pages/css.php');
        $css = ob_get_clean();

        $this->assertStringNotContainsString('@if', $css);
        $this->assertStringContainsString('.col-md-push-3 { left:25%; }', $css);
    }

}
