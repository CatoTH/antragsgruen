<?php

declare(strict_types=1);

namespace app\plugins\translation_claude;

/**
 * Appends one JSON-lines entry per Claude API call to runtime/logs/claude.log, for auditing: what was
 * sent, what came back, how long it took, and how many tokens were used. Instantiable (rather than
 * static, unlike Credentials) purely so ClaudeClient can take one as a constructor argument and tests
 * can point it at a temporary path instead of the real log file - the same injectability reasoning as
 * ClaudeClient's own Guzzle Client.
 *
 * Never throws: a logging failure must not break translation, let alone the motion/amendment save
 * it's attached to.
 */
class ClaudeLogger
{
    public function __construct(private readonly ?string $path = null)
    {
    }

    private function getPath(): string
    {
        return $this->path ?? (\Yii::$app->runtimePath . '/logs/claude.log');
    }

    /**
     * @param array<string, mixed> $request the JSON body sent to the API (system prompt, user
     *        message, tool schema, ...)
     * @param array<string, mixed>|string|null $response the parsed JSON body received back, or a
     *        plain-text summary (e.g. an exception message) when there was nothing to parse
     */
    public function log(
        string $status,
        array $request,
        array|string|null $response,
        float $durationSeconds,
        ?int $inputTokens,
        ?int $outputTokens,
    ): void {
        try {
            $entry = [
                'timestamp'    => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.uP'),
                'status'       => $status,
                'durationMs'   => (int) round($durationSeconds * 1000),
                'inputTokens'  => $inputTokens,
                'outputTokens' => $outputTokens,
                'request'      => $request,
                'response'     => $response,
            ];

            $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($line === false) {
                return;
            }

            $path = $this->getPath();
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $fp = @fopen($path, 'a');
            if ($fp) {
                fwrite($fp, $line . "\n");
                fclose($fp);
            }
        } catch (\Throwable) {
            // Logging must never break translation.
        }
    }
}
