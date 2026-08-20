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

    private function __construct(
        public readonly string $apiKey,
        public readonly string $model,
        public readonly ?string $baseUrl = null,
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

        if (is_file($path)) {
            $data = json_decode((string) file_get_contents($path), true);
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

        if ($apiKey === null || $apiKey === '') {
            return null;
        }

        return new self($apiKey, $model, $baseUrl);
    }
}
