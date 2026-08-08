<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\plugins\translation_claude\Credentials;
use Tests\Support\Helper\TestBase;

/**
 * Covers plugins/translation_claude/Credentials.php. Always writes to a temporary path, passed
 * explicitly to load(), rather than the real plugins/translation_claude/credentials.json - never
 * touch a developer's actual configured credentials.
 */
class TranslationClaudeCredentialsTest extends TestBase
{
    private ?string $tmpPath = null;

    protected function tearDown(): void
    {
        if ($this->tmpPath !== null && is_file($this->tmpPath)) {
            unlink($this->tmpPath);
        }
        parent::tearDown();
    }

    private function writeCredentialsFile(string $contents): string
    {
        $this->tmpPath = sys_get_temp_dir() . '/translation_claude_credentials_test_' . uniqid() . '.json';
        file_put_contents($this->tmpPath, $contents);
        return $this->tmpPath;
    }

    public function testReturnsNullWhenFileIsMissing(): void
    {
        $this->assertNull(Credentials::load(sys_get_temp_dir() . '/does-not-exist-' . uniqid() . '.json'));
    }

    public function testLoadsApiKeyAndModel(): void
    {
        $path = $this->writeCredentialsFile(json_encode([
            'apiKey' => 'sk-ant-test-key',
            'model' => 'claude-haiku-4-5',
        ], JSON_THROW_ON_ERROR));

        $credentials = Credentials::load($path);

        $this->assertNotNull($credentials);
        $this->assertSame('sk-ant-test-key', $credentials->apiKey);
        $this->assertSame('claude-haiku-4-5', $credentials->model);
    }

    public function testFallsBackToTheDefaultModelWhenNotSet(): void
    {
        $path = $this->writeCredentialsFile(json_encode(['apiKey' => 'sk-ant-test-key'], JSON_THROW_ON_ERROR));

        $credentials = Credentials::load($path);

        $this->assertNotNull($credentials);
        $this->assertNotSame('', $credentials->model);
    }

    public function testReturnsNullWithoutAnApiKey(): void
    {
        $path = $this->writeCredentialsFile(json_encode(['model' => 'claude-sonnet-5'], JSON_THROW_ON_ERROR));

        $this->assertNull(Credentials::load($path));
    }

    public function testReturnsNullWithAnEmptyApiKey(): void
    {
        $path = $this->writeCredentialsFile(json_encode(['apiKey' => ''], JSON_THROW_ON_ERROR));

        $this->assertNull(Credentials::load($path));
    }

    public function testReturnsNullOnMalformedJson(): void
    {
        $path = $this->writeCredentialsFile('{not valid json');

        $this->assertNull(Credentials::load($path));
    }

    public function testTheExampleFileMatchesTheExpectedShape(): void
    {
        $path = __DIR__ . '/../../plugins/translation_claude/credentials.example.json';
        $this->assertFileExists($path);
        $this->assertNotNull(Credentials::load($path));
    }
}
