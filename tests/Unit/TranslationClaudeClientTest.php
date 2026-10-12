<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\plugins\translation_claude\{ClaudeClient, ClaudeLogger, Credentials};
use GuzzleHttp\{Client, HandlerStack};
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Tests\Support\Helper\TestBase;

/**
 * Covers plugins/translation_claude/ClaudeClient.php against a mocked HTTP layer (GuzzleHttp\Handler\
 * MockHandler, the same technique models/db/... integration points use for testing external APIs
 * elsewhere, e.g. plugins/openslides/OpenslidesClient.php's injectable Client) - no real network
 * calls, ever. Also covers that every call - successful or not - is logged via ClaudeLogger, pointed
 * at a temporary file rather than the real runtime/logs/claude.log.
 */
class TranslationClaudeClientTest extends TestBase
{
    private ?string $tmpCredentialsPath = null;
    private ?string $tmpLogPath = null;

    protected function tearDown(): void
    {
        if ($this->tmpCredentialsPath !== null && is_file($this->tmpCredentialsPath)) {
            unlink($this->tmpCredentialsPath);
        }
        if ($this->tmpLogPath !== null && is_file($this->tmpLogPath)) {
            unlink($this->tmpLogPath);
        }
        parent::tearDown();
    }

    private ?MockHandler $mockHandler = null;

    private function clientWithResponses(Response ...$responses): ClaudeClient
    {
        return $this->clientWithCredentialsAndResponses(['apiKey' => 'sk-ant-test'], ...$responses);
    }

    /**
     * @param array<string, string> $credentialsData the contents of the temporary credentials.json
     */
    private function clientWithCredentialsAndResponses(array $credentialsData, Response ...$responses): ClaudeClient
    {
        $this->mockHandler = new MockHandler(array_values($responses));
        $handlerStack      = HandlerStack::create($this->mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $this->tmpCredentialsPath = sys_get_temp_dir() . '/translation_claude_client_test_' . uniqid() . '.json';
        file_put_contents($this->tmpCredentialsPath, json_encode($credentialsData, JSON_THROW_ON_ERROR));
        $credentials = Credentials::load($this->tmpCredentialsPath);

        $this->tmpLogPath = sys_get_temp_dir() . '/translation_claude_client_test_' . uniqid() . '.log';
        $logger           = new ClaudeLogger($this->tmpLogPath);

        return new ClaudeClient($credentials, $guzzleClient, $logger);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readLogEntries(): array
    {
        if (!is_file((string) $this->tmpLogPath)) {
            return [];
        }
        $lines = array_filter(explode("\n", (string) file_get_contents((string) $this->tmpLogPath)));

        return array_map(fn (string $line) => json_decode($line, true), array_values($lines));
    }

    private function structuredOutputResponseBody(array $output, int $inputTokens = 100, int $outputTokens = 50): string
    {
        return (string) json_encode([
            'content' => [
                ['type' => 'text', 'text' => json_encode($output, JSON_THROW_ON_ERROR)],
            ],
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => $inputTokens, 'output_tokens' => $outputTokens],
        ], JSON_THROW_ON_ERROR);
    }

    private function outputSchema(): array
    {
        return ['type' => 'object', 'additionalProperties' => false];
    }

    public function testReturnsTheStructuredOutputFromASuccessfulResponse(): void
    {
        $body   = $this->structuredOutputResponseBody(['translations' => [['sectionId' => 1, 'translatedHtml' => '<p>Translated</p>']]]);
        $client = $this->clientWithResponses(new Response(200, [], $body));

        $result = $client->sendStructuredMessage('system prompt', 'user message', $this->outputSchema());

        $this->assertSame(['translations' => [['sectionId' => 1, 'translatedHtml' => '<p>Translated</p>']]], $result);
    }

    public function testSendsTheSchemaAsStructuredOutputFormatWithoutForcedToolUse(): void
    {
        $client = $this->clientWithResponses(new Response(200, [], $this->structuredOutputResponseBody(['translations' => []])));

        $client->sendStructuredMessage('system prompt', 'user message', $this->outputSchema());

        $sent = json_decode((string) $this->mockHandler?->getLastRequest()?->getBody(), true);
        $this->assertSame(['type' => 'json_schema', 'schema' => $this->outputSchema()], $sent['output_config']['format']);
        $this->assertArrayNotHasKey('tool_choice', $sent);
        $this->assertArrayNotHasKey('tools', $sent);
    }

    /**
     * @param array<string, string> $credentialsData
     * @return array<string, mixed> the decoded JSON body of the single request sent
     */
    private function sentRequestBodyFor(array $credentialsData): array
    {
        $client = $this->clientWithCredentialsAndResponses(
            $credentialsData,
            new Response(200, [], $this->structuredOutputResponseBody(['translations' => []]))
        );
        $client->sendStructuredMessage('system prompt', 'user message', $this->outputSchema());

        return json_decode((string) $this->mockHandler?->getLastRequest()?->getBody(), true);
    }

    public function testDoesNotSendAnEffortUnlessConfigured(): void
    {
        $sent = $this->sentRequestBodyFor(['apiKey' => 'sk-ant-test']);

        $this->assertArrayNotHasKey('effort', $sent['output_config']);
    }

    public function testSendsTheConfiguredEffort(): void
    {
        $sent = $this->sentRequestBodyFor(['apiKey' => 'sk-ant-test', 'effort' => ' Low ']);

        $this->assertSame('low', $sent['output_config']['effort']);
    }

    public function testIgnoresAnUnknownEffort(): void
    {
        $sent = $this->sentRequestBodyFor(['apiKey' => 'sk-ant-test', 'effort' => 'extreme']);

        $this->assertArrayNotHasKey('effort', $sent['output_config']);
    }

    public function testIgnoresThinkingBlocksBeforeTheStructuredOutput(): void
    {
        $body = (string) json_encode([
            'content' => [
                ['type' => 'thinking', 'thinking' => '', 'signature' => 'sig'],
                ['type' => 'text', 'text' => '{"translations": []}'],
            ],
            'stop_reason' => 'end_turn',
        ], JSON_THROW_ON_ERROR);
        $client = $this->clientWithResponses(new Response(200, [], $body));

        $this->assertSame(['translations' => []], $client->sendStructuredMessage('system prompt', 'user message', $this->outputSchema()));
    }

    public function testReturnsNullAndLogsARefusal(): void
    {
        $body = (string) json_encode([
            'content' => [['type' => 'text', 'text' => '{"transl']],
            'stop_reason' => 'refusal',
            'stop_details' => ['type' => 'refusal', 'category' => 'general_harms', 'explanation' => null],
        ], JSON_THROW_ON_ERROR);
        $client = $this->clientWithResponses(new Response(200, [], $body));

        $this->assertNull($client->sendStructuredMessage('system prompt', 'user message', $this->outputSchema()));
        $this->assertSame('refusal', $this->readLogEntries()[0]['status']);
    }

    public function testReturnsNullAndLogsATruncatedResponse(): void
    {
        $body = (string) json_encode([
            'content' => [['type' => 'text', 'text' => '{"translations": [{"sectionId": 1, "transl']],
            'stop_reason' => 'max_tokens',
        ], JSON_THROW_ON_ERROR);
        $client = $this->clientWithResponses(new Response(200, [], $body));

        $this->assertNull($client->sendStructuredMessage('system prompt', 'user message', $this->outputSchema()));
        $this->assertSame('max_tokens', $this->readLogEntries()[0]['status']);
    }

    public function testReturnsNullOnANonSuccessResponse(): void
    {
        $client = $this->clientWithResponses(new Response(401, [], '{"error": "unauthorized"}'));

        $this->assertNull($client->sendStructuredMessage('system prompt', 'user message', $this->outputSchema()));
    }

    public function testReturnsNullWhenThereIsNoStructuredOutput(): void
    {
        $body   = (string) json_encode(['content' => [['type' => 'text', 'text' => 'not JSON']]], JSON_THROW_ON_ERROR);
        $client = $this->clientWithResponses(new Response(200, [], $body));

        $this->assertNull($client->sendStructuredMessage('system prompt', 'user message', $this->outputSchema()));
    }

    public function testReturnsNullOnUnparsableJson(): void
    {
        $client = $this->clientWithResponses(new Response(200, [], 'not json'));

        $this->assertNull($client->sendStructuredMessage('system prompt', 'user message', $this->outputSchema()));
    }

    public function testLogsASuccessfulRequestWithTimingAndTokens(): void
    {
        $body   = $this->structuredOutputResponseBody(['translations' => []], 123, 45);
        $client = $this->clientWithResponses(new Response(200, [], $body));

        $client->sendStructuredMessage('system prompt', 'user message', $this->outputSchema());

        $entries = $this->readLogEntries();
        $this->assertCount(1, $entries);
        $this->assertSame('success', $entries[0]['status']);
        $this->assertSame(123, $entries[0]['inputTokens']);
        $this->assertSame(45, $entries[0]['outputTokens']);
        $this->assertIsInt($entries[0]['durationMs']);
        $this->assertGreaterThanOrEqual(0, $entries[0]['durationMs']);
        $this->assertSame('system prompt', $entries[0]['request']['system']);
        $this->assertSame('user message', $entries[0]['request']['messages'][0]['content']);
    }

    public function testLogsAFailedRequestToo(): void
    {
        $client = $this->clientWithResponses(new Response(401, [], '{"error": "unauthorized"}'));

        $client->sendStructuredMessage('system prompt', 'user message', $this->outputSchema());

        $entries = $this->readLogEntries();
        $this->assertCount(1, $entries);
        $this->assertSame('http_401', $entries[0]['status']);
        $this->assertNull($entries[0]['inputTokens']);
    }
}
