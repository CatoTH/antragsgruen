<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use app\plugins\translation_claude\ClaudeClient;

/**
 * Records the last prompt/message it was called with and returns a canned response, instead of
 * making a real API call - used by TranslationClaudeSectionTranslatorTest. Subclassing (rather than
 * an interface) is fine here: ClaudeClient has no state of its own that this test double's
 * overridden sendMessage() needs.
 */
class RecordingClaudeClient extends ClaudeClient
{
    public ?string $lastSystemPrompt = null;
    public ?string $lastUserMessage = null;

    public function __construct(private readonly string $response)
    {
    }

    public function sendMessage(string $systemPrompt, string $userMessage): ?string
    {
        $this->lastSystemPrompt = $systemPrompt;
        $this->lastUserMessage = $userMessage;

        return $this->response;
    }
}
