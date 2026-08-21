<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\plugins\translation_claude\Credentials;
use Tests\Support\Helper\TestBase;

class TranslationClaudeCredentialsTest extends TestBase
{
    private ?string $tmpFile = null;

    protected function tearDown(): void
    {
        if ($this->tmpFile !== null && is_file($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        unset($_ENV['ANTHROPIC_API_KEY'], $_ENV['ANTHROPIC_MODEL'], $_ENV['ANTHROPIC_BASE_URL']);
        unset($_ENV['CLAUDE_API_KEY'], $_ENV['CLAUDE_MODEL'], $_ENV['CLAUDE_BASE_URL']);
        putenv('ANTHROPIC_API_KEY');
        putenv('ANTHROPIC_MODEL');
        putenv('ANTHROPIC_BASE_URL');
        putenv('CLAUDE_API_KEY');
        putenv('CLAUDE_MODEL');
        putenv('CLAUDE_BASE_URL');
        parent::tearDown();
    }

    public function testLoadsFromJsonFileWithBaseUrl(): void
    {
        $this->tmpFile = sys_get_temp_dir() . '/test_credentials_' . uniqid() . '.json';
        file_put_contents($this->tmpFile, json_encode([
            'apiKey' => 'sk-ant-testkey',
            'model' => 'claude-3-7-sonnet',
            'baseUrl' => 'http://litellm-proxy:4000',
        ]));

        $creds = Credentials::load($this->tmpFile);

        $this->assertNotNull($creds);
        $this->assertSame('sk-ant-testkey', $creds->apiKey);
        $this->assertSame('claude-3-7-sonnet', $creds->model);
        $this->assertSame('http://litellm-proxy:4000', $creds->baseUrl);
    }

    public function testLoadsFromEnvironmentVariablesWhenFileMissing(): void
    {
        $_ENV['ANTHROPIC_API_KEY'] = 'sk-ant-envkey';
        $_ENV['ANTHROPIC_MODEL'] = 'claude-3-5-haiku';
        $_ENV['ANTHROPIC_BASE_URL'] = 'https://proxy.example.org/v1/';

        $creds = Credentials::load('/non/existent/path/credentials.json');

        $this->assertNotNull($creds);
        $this->assertSame('sk-ant-envkey', $creds->apiKey);
        $this->assertSame('claude-3-5-haiku', $creds->model);
        $this->assertSame('https://proxy.example.org/v1', $creds->baseUrl);
    }

    public function testReturnsNullWhenNoCredentialsFound(): void
    {
        $creds = Credentials::load('/non/existent/path/credentials.json');
        $this->assertNull($creds);
    }
}
