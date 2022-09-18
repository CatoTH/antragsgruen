<?php

namespace app\components;

use ScssPhp\ScssPhp\Parser;

/**
 * This is a workaround for missing math.div support in scssphp.
 * https://github.com/scssphp/scssphp/issues/419
 * https://github.com/scssphp/scssphp/issues/55
 *
 * It replaces math.div by old-style division using regular expressions and removes the @use "sass:math"; references
 *
 * Relevant test case: appearance/CustomThemeCept
 */
class CssCompiler extends \ScssPhp\ScssPhp\Compiler
{
    protected function parserFactory($path): Parser
    {
        $cssOnly = false;

        if ($path !== null && substr($path, -4) === '.css') {
            $cssOnly = true;
        }

        $parser = new class($path, \count($this->sourceNames), $this->encoding, $this->cache, $cssOnly) extends Parser {
            public function __construct($sourceName, $sourceIndex = 0, $encoding = 'utf-8', \ScssPhp\ScssPhp\Cache $cache = null, $cssOnly = false, \ScssPhp\ScssPhp\Logger\LoggerInterface $logger = null)
            {
                parent::__construct($sourceName, $sourceIndex, $encoding, $cache, $cssOnly, $logger);
            }

            public function parse($buffer)
            {
                $buffer = str_replace('@use "sass:math";', '', $buffer);
                $buffer = preg_replace_callback('/math\.div\((?<first>[^,]*), (?<second>[^)]+)\)/siu', function ($matches): string {
                    return $matches['first'] . ' / ' . $matches['second'];
                }, $buffer);

                return parent::parse($buffer);
            }
        };

        $this->sourceNames[] = $path;
        $this->addParsedFile($path);

        return $parser;
    }
}
