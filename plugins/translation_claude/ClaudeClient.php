<?php

declare(strict_types=1);

namespace app\plugins\translation_claude;

use GuzzleHttp\{Client, RequestOptions};

/**
 * Thin wrapper around Anthropic's Messages API, using a single, non-streaming request with structured
 * outputs (output_config.format) per call to get a reliably structured (rather than free-form-text)
 * response back - see Prompts::translationsOutputSchema(). Structured outputs are used instead of a
 * forced tool call because newer models (e.g. claude-sonnet-5-5) reject a forced tool_choice.
 * Translation is an optional enhancement (see SectionTranslator/Module), so any failure here - a network error, a non-2xx response, an unexpected
 * response shape, a refusal - is swallowed and reported as null, never thrown: it must never break the
 * motion/amendment save it's attached to. Every call is logged via ClaudeLogger regardless of outcome.
 */
class ClaudeClient
{
    private const API_BASE_URI = 'https://api.anthropic.com';
    private const MESSAGES_PATH = '/v1/messages';
    private const ANTHROPIC_VERSION = '2023-06-01';
    private const MAX_TOKENS = 128000;
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
     * Sends $userMessage to Claude, constraining the response to the JSON schema $outputSchema (see
     * Prompts::translationsOutputSchema()) instead of free-form text - reliable to parse regardless of
     * how many translations are batched into one call. Returns the already-JSON-decoded response, or
     * null on any failure.
     *
     * @param array<string, mixed> $outputSchema
     * @return array<string, mixed>|null
     */
    public function sendStructuredMessage(string $systemPrompt, string $userMessage, array $outputSchema): ?array
    {
        $requestBody = [
            'model' => $this->credentials->model,
            'max_tokens' => self::MAX_TOKENS,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
            'output_config' => [
                'format' => ['type' => 'json_schema', 'schema' => $outputSchema],
            ],
        ];
        // Only sent when configured, so the model's own default applies otherwise (see Credentials::EFFORT_LEVELS)
        if ($this->credentials->effort !== null) {
            $requestBody['output_config']['effort'] = $this->credentials->effort;
        }

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

            // A refusal or a truncated response may not match the schema - don't try to parse it.
            $stopReason = is_array($data) ? ($data['stop_reason'] ?? null) : null;
            if ($stopReason === 'refusal' || $stopReason === 'max_tokens') {
                $status = $stopReason;
                \Yii::error('Claude API response stopped with stop_reason ' . $stopReason, 'translation_claude');
                return null;
            }

            // Thinking blocks may precede the answer; the JSON itself is in the text block(s).
            $text = '';
            foreach ((is_array($data) ? ($data['content'] ?? []) : []) as $block) {
                if (is_array($block) && ($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                    $text .= $block['text'];
                }
            }

            $output = json_decode($text, true);
            if (!is_array($output)) {
                $status = 'no_structured_output';
                \Yii::error('Claude API response had no usable structured output', 'translation_claude');
                return null;
            }

            $status = 'success';
            return $output;
        } catch (\Throwable $e) {
            $response = $e->getMessage();
            \Yii::error('Claude API request threw: ' . $e->getMessage(), 'translation_claude');
            return null;
        } finally {
            $this->logger->log($status, $requestBody, $response, microtime(true) - $start, $inputTokens, $outputTokens);
        }
    }
}
