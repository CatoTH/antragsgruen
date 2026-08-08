<?php

declare(strict_types=1);

namespace app\plugins\translation_claude;

use GuzzleHttp\{Client, RequestOptions};

/**
 * Thin wrapper around Anthropic's Messages API (a single, non-streaming, non-tool-use request).
 * Translation is an optional enhancement (see SectionTranslator/Module), so any failure here - a
 * network error, a non-2xx response, an unexpected response shape - is swallowed and reported as
 * null, never thrown: it must never break the motion/amendment save it's attached to.
 */
class ClaudeClient
{
    private const API_BASE_URI = 'https://api.anthropic.com';
    private const MESSAGES_PATH = '/v1/messages';
    private const ANTHROPIC_VERSION = '2023-06-01';
    private const MAX_TOKENS = 8192;
    private const TIMEOUT_SECONDS = 60;

    private ?Client $client;

    public function __construct(
        private readonly Credentials $credentials,
        ?Client $client = null,
    ) {
        $this->client = $client;
    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client(['base_uri' => self::API_BASE_URI]);
        }
        return $this->client;
    }

    public function sendMessage(string $systemPrompt, string $userMessage): ?string
    {
        try {
            $response = $this->getClient()->post(self::MESSAGES_PATH, [
                RequestOptions::HEADERS => [
                    'x-api-key' => $this->credentials->apiKey,
                    'anthropic-version' => self::ANTHROPIC_VERSION,
                    'content-type' => 'application/json',
                ],
                RequestOptions::JSON => [
                    'model' => $this->credentials->model,
                    'max_tokens' => self::MAX_TOKENS,
                    'system' => $systemPrompt,
                    'messages' => [
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                ],
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::TIMEOUT => self::TIMEOUT_SECONDS,
            ]);

            if ($response->getStatusCode() !== 200) {
                \Yii::error(
                    'Claude API request failed with HTTP ' . $response->getStatusCode() . ': ' . $response->getBody()->getContents(),
                    'translation_claude'
                );
                return null;
            }

            $data = json_decode($response->getBody()->getContents(), true);
            $text = $data['content'][0]['text'] ?? null;
            if (!is_string($text) || trim($text) === '') {
                \Yii::error('Claude API response had no usable text content', 'translation_claude');
                return null;
            }

            return trim($text);
        } catch (\Throwable $e) {
            \Yii::error('Claude API request threw: ' . $e->getMessage(), 'translation_claude');
            return null;
        }
    }
}
