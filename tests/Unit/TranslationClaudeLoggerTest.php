<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\plugins\translation_claude\ClaudeLogger;
use Tests\Support\Helper\TestBase;

/**
 * Covers plugins/translation_claude/ClaudeLogger.php in isolation (ClaudeClientTest covers it being
 * called correctly from an actual request). Always constructed with an explicit temporary path -
 * never touches the real runtime/logs/claude.log.
 */
class TranslationClaudeLoggerTest extends TestBase
{
    private ?string $tmpPath = null;
    private ?string $tmpDir = null;

    protected function tearDown(): void
    {
        if ($this->tmpPath !== null && is_file($this->tmpPath)) {
            unlink($this->tmpPath);
        }
        if ($this->tmpDir !== null && is_dir($this->tmpDir)) {
            rmdir($this->tmpDir);
        }
        parent::tearDown();
    }

    private function readEntries(): array
    {
        $lines = array_filter(explode("\n", (string) file_get_contents((string) $this->tmpPath)));

        return array_map(fn (string $line) => json_decode($line, true), array_values($lines));
    }

    public function testWritesAJsonLineEntryWithAllFields(): void
    {
        $this->tmpPath = sys_get_temp_dir() . '/claude_logger_test_' . uniqid() . '.log';
        $logger        = new ClaudeLogger($this->tmpPath);

        $logger->log(
            'success',
            ['system' => 'a prompt', 'messages' => [['role' => 'user', 'content' => 'hi']]],
            ['translations' => []],
            0.25,
            111,
            22
        );

        $entries = $this->readEntries();
        $this->assertCount(1, $entries);
        $this->assertSame('success', $entries[0]['status']);
        $this->assertSame(111, $entries[0]['inputTokens']);
        $this->assertSame(22, $entries[0]['outputTokens']);
        $this->assertSame(250, $entries[0]['durationMs']);
        $this->assertSame('a prompt', $entries[0]['request']['system']);
        $this->assertSame(['translations' => []], $entries[0]['response']);
        $this->assertArrayHasKey('timestamp', $entries[0]);
    }

    public function testAppendsRatherThanOverwriting(): void
    {
        $this->tmpPath = sys_get_temp_dir() . '/claude_logger_test_' . uniqid() . '.log';
        $logger        = new ClaudeLogger($this->tmpPath);

        $logger->log('success', [], null, 0.1, null, null);
        $logger->log('http_500', [], 'server error', 0.2, null, null);

        $entries = $this->readEntries();
        $this->assertCount(2, $entries);
        $this->assertSame('success', $entries[0]['status']);
        $this->assertSame('http_500', $entries[1]['status']);
        $this->assertSame('server error', $entries[1]['response']);
    }

    public function testCreatesTheLogDirectoryIfMissing(): void
    {
        $this->tmpDir  = sys_get_temp_dir() . '/claude_logger_test_dir_' . uniqid();
        $this->tmpPath = $this->tmpDir . '/claude.log';
        $logger        = new ClaudeLogger($this->tmpPath);

        $logger->log('success', [], null, 0.1, null, null);

        $this->assertFileExists($this->tmpPath);
    }

    public function testNeverThrowsWhenThePathIsUnwritable(): void
    {
        // A directory can never be fopen()'d for writing - this simulates a permissions/path problem
        // without relying on actually unwritable filesystem permissions (which differ e.g. when
        // running as root).
        $this->tmpDir = sys_get_temp_dir() . '/claude_logger_test_unwritable_' . uniqid();
        mkdir($this->tmpDir);
        $logger = new ClaudeLogger($this->tmpDir);

        $logger->log('success', [], null, 0.1, null, null);

        $this->assertTrue(true); // Reaching this line without an exception is the actual assertion.
    }
}
