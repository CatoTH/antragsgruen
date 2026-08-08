<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use app\plugins\translation_claude\{Credentials, Module};

/**
 * Forces plugins/translation_claude/Module.php's "no credentials configured" branch
 * deterministically, regardless of whether a real plugins/translation_claude/credentials.json
 * happens to exist in this environment (e.g. because a developer configured one locally to try the
 * feature out) - used by TranslationClaudeModuleTest.
 */
class TranslationClaudeModuleWithoutCredentials extends Module
{
    protected static function loadCredentials(): ?Credentials
    {
        return null;
    }
}
