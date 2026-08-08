<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use app\plugins\translation_claude\ClaudeClient;

/**
 * Records the last request it was called with and returns a canned batch of translations, instead of
 * making a real API call - used by TranslationClaudeSectionTranslatorTest. Subclassing (rather than an
 * interface) is fine here: ClaudeClient has no state of its own that this test double's overridden
 * sendStructuredMessage() needs.
 */
class RecordingClaudeClient extends ClaudeClient
{
    public ?string $lastSystemPrompt = null;
    public ?string $lastUserMessage = null;
    /** @var array<string, mixed>|null */
    public ?array $lastToolSchema = null;
    /** @var array<int, array<string, mixed>>|null the decoded "tasks" from the last request */
    public ?array $lastTasks = null;

    /**
     * @param array<int, string> $responsesBySectionId sectionId => translated content to return for
     *        that task; a sectionId present in the request but absent here is simply omitted from the
     *        canned response, simulating "Claude couldn't/didn't translate this one".
     */
    public function __construct(private readonly array $responsesBySectionId)
    {
    }

    public function sendStructuredMessage(string $systemPrompt, string $userMessage, array $toolSchema): ?array
    {
        $this->lastSystemPrompt = $systemPrompt;
        $this->lastUserMessage = $userMessage;
        $this->lastToolSchema = $toolSchema;

        $decoded = json_decode($userMessage, true);
        $this->lastTasks = is_array($decoded) ? ($decoded['tasks'] ?? []) : [];

        $translations = [];
        foreach ($this->lastTasks as $task) {
            $sectionId = $task['sectionId'] ?? null;
            if (is_int($sectionId) && isset($this->responsesBySectionId[$sectionId])) {
                $translations[] = ['sectionId' => $sectionId, 'translatedHtml' => $this->responsesBySectionId[$sectionId]];
            }
        }

        return ['translations' => $translations];
    }
}
