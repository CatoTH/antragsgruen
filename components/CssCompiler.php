<?php

declare(strict_types=1);

namespace app\components;

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;

/**
 * This is a workaround for missing math.div support in scssphp.
 * https://github.com/scssphp/scssphp/issues/419
 * https://github.com/scssphp/scssphp/issues/55
 *
 * It replaces math.div by old-style division using regular expressions and removes the @use "sass:math"; references
 *
 * Relevant test case: appearance/CustomThemeCept
 */
class CssCompiler
{
    public function compileCss(string $css): string
    {
        $tmpDir = \app\models\settings\AntragsgruenApp::getInstance()->getTmpDir();

        $this->copyAndFixScss($tmpDir);

        $scss = new Compiler();
        $scss->addImportPath($tmpDir . 'css-compile');
        $scss->setOutputStyle(OutputStyle::COMPRESSED);

        return $scss->compileString($css)->getCss();
    }

    private function copyAndFixScss(string $tmpDir): void
    {
        $filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $filesystem->mirror(\Yii::$app->basePath . '/web/css', $tmpDir . 'css-compile');
        $filesystem->mirror(\Yii::$app->basePath . '/web/fonts', $tmpDir . 'fonts');

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            if (!str_ends_with($file->getPathname(), 'scss')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if ($content) {
                file_put_contents($file->getPathname(), $this->fixCss($content));
            }
        }
    }

    private function fixCss(String $css): string
    {
        $css = str_replace('@use "sass:math";', '', $css);
        $css = preg_replace_callback('/math\.div\((?<first>[^,]*), (?<second>[^)]+)\)/siu', function ($matches): string {
            return $matches['first'] . ' / ' . $matches['second'];
        }, $css);

        return $css;
    }
}
