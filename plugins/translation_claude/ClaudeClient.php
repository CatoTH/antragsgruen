<?php

declare(strict_types=1);

namespace app\plugins\translation_claude;

use GuzzleHttp\{Client, RequestOptions};

/**
 * Thin wrapper around Anthropic's Messages API, using a single, non-streaming, tool-use request per
 * call to get a reliably structured (rather than free-form-text) response back - see
 * Prompts::translationsToolSchema(). Translation is an optional enhancement (see
 * SectionTranslator/Module), so any failure here - a network error, a non-2xx response, an unexpected
 * response shape - is swallowed and reported as null, never thrown: it must never break the
 * motion/amendment save it's attached to. Every call is logged via ClaudeLogger regardless of outcome.
 */
class ClaudeClient
{
    private const API_BASE_URI = 'https://api.anthropic.com';
    private const MESSAGES_PATH = '/v1/messages';
    private const ANTHROPIC_VERSION = '2023-06-01';
    private const MAX_TOKENS = 8192;
    private const TIMEOUT_SECONDS = 120;

    private ?Client $client;

    public function __construct(
        private readonly Credentials $credentials,
        ?Client $client = null,
        private readonly ClaudeLogger $logger = new ClaudeLogger(),
    ) {
        $this->client = $client;
    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            $baseUri = $this->credentials->baseUrl ?: self::API_BASE_URI;
            $this->client = new Client(['base_uri' => $baseUri]);
        }
        return $this->client;
    }

    /**
     * Sends $userMessage to Claude, forcing a response structured according to $toolSchema (an
     * Anthropic tool/"function calling" definition, see Prompts::translationsToolSchema()) instead of
     * free-form text - reliable to parse regardless of how many translations are batched into one
     * call. Returns the tool call's already-JSON-decoded "input", or null on any failure.
     *
     * @param array<string, mixed> $toolSchema
     * @return array<string, mixed>|null
     */
    public function sendStructuredMessage(string $systemPrompt, string $userMessage, array $toolSchema): ?array
    {
        $requestBody = [
            'model' => $this->credentials->model,
            'max_tokens' => self::MAX_TOKENS,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
            'tools' => [$toolSchema],
            'tool_choice' => ['type' => 'tool', 'name' => $toolSchema['name']],
        ];

        $start = microtime(true);
        $status = 'exception';
        $inputTokens = null;
        $outputTokens = null;
        $response = null;

        try {
            $httpResponse = $this->getClient()->post(self::MESSAGES_PATH, [
                RequestOptions::HEADERS => [
                    'x-api-key' => $this->credentials->apiKey,
                    'anthropic-version' => self::ANTHROPIC_VERSION,
                    'content-type' => 'application/json',
                ],
                RequestOptions::JSON => $requestBody,
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::TIMEOUT => self::TIMEOUT_SECONDS,
            ]);

            $body = $httpResponse->getBody()->getContents();

            if ($httpResponse->getStatusCode() !== 200) {
                $status = 'http_' . $httpResponse->getStatusCode();
                $response = $body;
                \Yii::error('Claude API request failed with HTTP ' . $httpResponse->getStatusCode() . ': ' . $body, 'translation_claude');
                return null;
            }

            $data = json_decode($body, true);
            $response = is_array($data) ? $data : $body;
            $inputTokens = $data['usage']['input_tokens'] ?? null;
            $outputTokens = $data['usage']['output_tokens'] ?? null;

            $toolInput = null;
            foreach ((is_array($data) ? ($data['content'] ?? []) : []) as $block) {
                if (is_array($block) && ($block['type'] ?? null) === 'tool_use') {
                    $toolInput = $block['input'] ?? null;
                    break;
                }
            }

            if (!is_array($toolInput)) {
                $status = 'no_tool_use';
                \Yii::error('Claude API response had no usable tool_use content', 'translation_claude');
                return null;
            }

            $status = 'success';
            return $toolInput;
        } catch (\Throwable $e) {
            $response = $e->getMessage();
            \Yii::error('Claude API request threw: ' . $e->getMessage(), 'translation_claude');
            return null;
        } finally {
            $this->logger->log($status, $requestBody, $response, microtime(true) - $start, $inputTokens, $outputTokens);
        }
    }
}
