<?php

declare(strict_types=1);

namespace app\plugins\translation_claude;

/**
 * Loads plugins/translation_claude/credentials.json (gitignored - see credentials.example.json for
 * the expected shape). Absent or malformed credentials simply mean the plugin has nothing to do -
 * load() returning null tells Module to decline (SectionAutofill leaves the section empty), never
 * throws, since a mistyped/missing credentials file must not break a motion/amendment save.
 */
class Credentials
{
    private const DEFAULT_MODEL = 'claude-sonnet-5';

    /**
     * Values accepted for the optional "effort" setting (sent as output_config.effort): how much
     * thinking and how many output tokens the model spends per request. Lower is faster and cheaper,
     * higher is more thorough.
     * - "low":    fastest and cheapest; thinking is kept short or skipped for simple requests.
     * - "medium": a cost-saving step down from "high" where quality holds.
     * - "high":   thorough; the API's default on Sonnet 5 / Sonnet 5.5 when no effort is sent.
     * - "xhigh":  more thorough still; mostly useful for hard coding/agentic work.
     * - "max":    the most thorough and the most expensive.
     * Not every model supports every level (e.g. Haiku 4.5 rejects the parameter altogether, Opus 4.6
     * has no "xhigh"), and the default differs between models (Opus 5.5 defaults to "medium") - an
     * unsupported value makes the API reject the request, which disables translation.
     */
    public const EFFORT_LEVELS = ['low', 'medium', 'high', 'xhigh', 'max'];

    /**
     * @param string|null $effort one of EFFORT_LEVELS, or null to not send it and use the model's default
     */
    private function __construct(
        public readonly string $apiKey,
        public readonly string $model,
        public readonly ?string $baseUrl = null,
        public readonly ?string $effort = null,
    ) {
    }

    /**
     * @param string|null $path only ever overridden by tests, to avoid touching a developer's real
     *        credentials.json
     */
    public static function load(?string $path = null): ?self
    {
        $path ??= __DIR__ . '/credentials.json';
        $apiKey = null;
        $model = self::DEFAULT_MODEL;
        $baseUrl = null;
        $effort = null;

        if (is_file($path)) {
            $data = \ColinODell\Json5\Json5Decoder::decode((string) file_get_contents($path), true);
            if (is_array($data)) {
                if (isset($data['apiKey']) && is_string($data['apiKey']) && $data['apiKey'] !== '') {
                    $apiKey = $data['apiKey'];
                }
                if (isset($data['model']) && is_string($data['model']) && $data['model'] !== '') {
                    $model = $data['model'];
                }
                if (isset($data['baseUrl']) && is_string($data['baseUrl']) && $data['baseUrl'] !== '') {
                    $baseUrl = rtrim(trim($data['baseUrl']), '/');
                }
                if (isset($data['effort']) && is_string($data['effort'])) {
                    $effort = self::parseEffort($data['effort']);
                }
            }
        }

        // Environment variables fallback / override
        $envKey = getenv('ANTHROPIC_API_KEY') ?: getenv('CLAUDE_API_KEY') ?: ($_ENV['ANTHROPIC_API_KEY'] ?? $_ENV['CLAUDE_API_KEY'] ?? null);
        if ($envKey !== null && is_string($envKey) && trim($envKey) !== '') {
            $apiKey = trim($envKey);
        }

        $envModel = getenv('ANTHROPIC_MODEL') ?: getenv('CLAUDE_MODEL') ?: ($_ENV['ANTHROPIC_MODEL'] ?? $_ENV['CLAUDE_MODEL'] ?? null);
        if ($envModel !== null && is_string($envModel) && trim($envModel) !== '') {
            $model = trim($envModel);
        }

        $envBaseUrl = getenv('ANTHROPIC_BASE_URL') ?: getenv('CLAUDE_BASE_URL') ?: ($_ENV['ANTHROPIC_BASE_URL'] ?? $_ENV['CLAUDE_BASE_URL'] ?? null);
        if ($envBaseUrl !== null && is_string($envBaseUrl) && trim($envBaseUrl) !== '') {
            $baseUrl = rtrim(trim($envBaseUrl), '/');
        }

        // Deliberately plugin-specific: generic names like CLAUDE_EFFORT are already used by other tools
        $envEffort = getenv('TRANSLATION_CLAUDE_EFFORT') ?: ($_ENV['TRANSLATION_CLAUDE_EFFORT'] ?? null);
        if ($envEffort !== null && is_string($envEffort) && trim($envEffort) !== '') {
            $effort = self::parseEffort($envEffort);
        }

        if ($apiKey === null || $apiKey === '') {
            return null;
        }

        return new self($apiKey, $model, $baseUrl, $effort);
    }

    /**
     * An empty or unknown value falls back to null (the model's default) instead of being sent: a
     * typo in the configuration must not make every request fail.
     */
    private static function parseEffort(string $value): ?string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }
        if (!in_array($value, self::EFFORT_LEVELS, true)) {
            \Yii::warning('Ignoring unknown effort "' . $value . '", expected one of: ' . implode(', ', self::EFFORT_LEVELS), 'translation_claude');
            return null;
        }
        return $value;
    }
}
