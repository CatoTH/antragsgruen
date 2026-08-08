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
    ) {
    }

    /**
     * @param string|null $path only ever overridden by tests, to avoid touching a developer's real
     *        credentials.json
     */
    public static function load(?string $path = null): ?self
    {
        $path ??= __DIR__ . '/credentials.json';
        if (!is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data) || !isset($data['apiKey']) || !is_string($data['apiKey']) || $data['apiKey'] === '') {
            return null;
        }

        $model = self::DEFAULT_MODEL;
        if (isset($data['model']) && is_string($data['model']) && $data['model'] !== '') {
            $model = $data['model'];
        }

        return new self($data['apiKey'], $model);
    }
}
