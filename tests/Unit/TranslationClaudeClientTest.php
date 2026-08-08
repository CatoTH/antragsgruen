<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\plugins\translation_claude\{ClaudeClient, Credentials};
use GuzzleHttp\{Client, HandlerStack};
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Tests\Support\Helper\TestBase;

/**
 * Covers plugins/translation_claude/ClaudeClient.php against a mocked HTTP layer (GuzzleHttp\Handler\
 * MockHandler, the same technique models/db/... integration points use for testing external APIs
 * elsewhere, e.g. plugins/openslides/OpenslidesClient.php's injectable Client) - no real network
 * calls, ever.
 */
class TranslationClaudeClientTest extends TestBase
{
    private function clientWithResponses(Response ...$responses): ClaudeClient
    {
        $mock = new MockHandler(array_values($responses));
        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $credentials = Credentials::load($this->writeTempCredentials());

        return new ClaudeClient($credentials, $guzzleClient);
    }

    private ?string $tmpCredentialsPath = null;

    private function writeTempCredentials(): string
    {
        $this->tmpCredentialsPath = sys_get_temp_dir() . '/translation_claude_client_test_' . uniqid() . '.json';
        file_put_contents($this->tmpCredentialsPath, json_encode(['apiKey' => 'sk-ant-test'], JSON_THROW_ON_ERROR));
        return $this->tmpCredentialsPath;
    }

    protected function tearDown(): void
    {
        if ($this->tmpCredentialsPath !== null && is_file($this->tmpCredentialsPath)) {
            unlink($this->tmpCredentialsPath);
        }
        parent::tearDown();
    }

    public function testReturnsTheTextFromASuccessfulResponse(): void
    {
        $body = json_encode(['content' => [['type' => 'text', 'text' => '<p>Translated</p>']]], JSON_THROW_ON_ERROR);
        $client = $this->clientWithResponses(new Response(200, [], $body));

        $this->assertSame('<p>Translated</p>', $client->sendMessage('system prompt', 'user message'));
    }

    public function testReturnsNullOnANonSuccessResponse(): void
    {
        $client = $this->clientWithResponses(new Response(401, [], '{"error": "unauthorized"}'));

        $this->assertNull($client->sendMessage('system prompt', 'user message'));
    }

    public function testReturnsNullWhenTheResponseHasNoTextContent(): void
    {
        $body = json_encode(['content' => []], JSON_THROW_ON_ERROR);
        $client = $this->clientWithResponses(new Response(200, [], $body));

        $this->assertNull($client->sendMessage('system prompt', 'user message'));
    }

    public function testReturnsNullOnUnparsableJson(): void
    {
        $client = $this->clientWithResponses(new Response(200, [], 'not json'));

        $this->assertNull($client->sendMessage('system prompt', 'user message'));
    }
}
