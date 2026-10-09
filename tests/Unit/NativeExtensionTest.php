<?php

namespace Tests\Unit;

use app\components\diff\{Diff, Engine};
use Tests\Support\Helper\TestBase;

/**
 * Compares the optional native extension (native/) with the PHP implementation.
 * Skipped if the extension is not loaded, see native/README.md.
 */
class NativeExtensionTest extends TestBase
{
    private const WORDS = ['Wir ', 'fordern ', 'eine ', 'schnelle ', 'Umsetzung', '.', ':', 'der ', 'die ', 'Klimaziele ', 'Bürger*innen ',
        'und ', 'sozial-', 'gerechte ', '###LINENUMBER###', '<p>', '</p>', '<strong>', '</strong>', '<li>', '<li value="2">', '<ul>', '<ol start="3">'];

    protected function setUp(): void
    {
        parent::setUp();
        if (!function_exists('antragsgruen_native_version')) {
            $this->markTestSkipped('The native extension is not loaded');
        }
    }

    protected function tearDown(): void
    {
        Engine::$useNativeExtension = true;
        parent::tearDown();
    }

    private function withAndWithoutExtension(callable $callback): array
    {
        Engine::$useNativeExtension = false;
        $php = $callback();
        Engine::$useNativeExtension = true;
        $this->assertTrue(Engine::nativeLcsAvailable());
        $native = $callback();

        return [$php, $native];
    }

    /**
     * @return string[]
     */
    private function randomTokens(int $length): array
    {
        $tokens = [];
        for ($i = 0; $i < $length; $i++) {
            $tokens[] = self::WORDS[mt_rand(0, count(self::WORDS) - 1)];
        }
        return $tokens;
    }

    /**
     * @param string[] $tokens
     * @return string[]
     */
    private function mutateTokens(array $tokens, int $changes): array
    {
        for ($i = 0; $i < $changes; $i++) {
            $pos = mt_rand(0, max(0, count($tokens) - 1));
            switch (mt_rand(0, 2)) {
                case 0:
                    $tokens[$pos] = self::WORDS[mt_rand(0, count(self::WORDS) - 1)];
                    break;
                case 1:
                    array_splice($tokens, $pos, 0, [self::WORDS[mt_rand(0, count(self::WORDS) - 1)]]);
                    break;
                default:
                    array_splice($tokens, $pos, 1);
            }
        }
        return $tokens;
    }

    public function testCompareArraysIsIdentical(): void
    {
        mt_srand(1);
        for ($run = 0; $run < 500; $run++) {
            $tokens1 = $this->randomTokens(mt_rand(0, 120));
            $tokens2 = $this->mutateTokens($tokens1, mt_rand(0, 40));
            $relaxedTags = ($run % 2 === 0);
            $ignoreStr = ($run % 3 === 0 ? '' : '###LINENUMBER###');

            [$php, $native] = $this->withAndWithoutExtension(function () use ($tokens1, $tokens2, $relaxedTags, $ignoreStr) {
                $engine = new Engine();
                $engine->setIgnoreStr($ignoreStr);
                return $engine->compareArrays($tokens1, $tokens2, relaxedTags: $relaxedTags);
            });
            $this->assertSame($php, $native, 'Run ' . $run);
        }
    }

    public function testLineDiffIsIdentical(): void
    {
        mt_srand(2);
        for ($run = 0; $run < 200; $run++) {
            $words = $this->randomTokens(mt_rand(5, 300));
            $words = array_values(array_filter($words, fn (string $word) => !str_starts_with($word, '<')));
            $lineOld = '<p>' . implode('', $words) . '</p>';
            $lineNew = '<p>' . implode('', $this->mutateTokens($words, mt_rand(1, 60))) . '</p>';

            [$php, $native] = $this->withAndWithoutExtension(function () use ($lineOld, $lineNew) {
                $diff = new Diff();
                $diff->setIgnoreStr('###LINENUMBER###');
                return $diff->computeLineDiff($lineOld, $lineNew);
            });
            $this->assertSame($php, $native, 'Run ' . $run);
        }
    }
}
