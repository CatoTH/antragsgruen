<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\plugins\translation_claude\Prompts;
use Tests\Support\Helper\TestBase;

/**
 * Covers plugins/translation_claude/Prompts.php: the exact wording is free to evolve, but the
 * structural guarantees the rest of the codebase (and the feature request) cares about must hold -
 * languages are named correctly, the HTML-preservation rules are always included, and the
 * amendment-specific instruction to stay close to the existing motion translation is present.
 */
class TranslationClaudePromptsTest extends TestBase
{
    public function testMotionSectionPromptNamesBothLanguages(): void
    {
        $prompt = Prompts::motionSectionSystemPrompt('Deutsch', 'English');

        $this->assertStringContainsString('Deutsch', $prompt);
        $this->assertStringContainsString('English', $prompt);
    }

    public function testMotionSectionPromptIncludesTheHtmlPreservationRules(): void
    {
        $prompt = Prompts::motionSectionSystemPrompt('Deutsch', 'English');

        $this->assertStringContainsString('class', $prompt);
        $this->assertStringContainsString('whitespace', $prompt);
        $this->assertStringContainsString('HTML', $prompt);
    }

    public function testAmendmentSectionPromptNamesBothLanguages(): void
    {
        $prompt = Prompts::amendmentSectionSystemPrompt('Deutsch', 'English');

        $this->assertStringContainsString('Deutsch', $prompt);
        $this->assertStringContainsString('English', $prompt);
    }

    public function testAmendmentSectionPromptIncludesTheHtmlPreservationRules(): void
    {
        $prompt = Prompts::amendmentSectionSystemPrompt('Deutsch', 'English');

        $this->assertStringContainsString('class', $prompt);
        $this->assertStringContainsString('whitespace', $prompt);
    }

    public function testAmendmentSectionPromptInstructsStayingCloseToTheExistingTranslation(): void
    {
        $prompt = Prompts::amendmentSectionSystemPrompt('Deutsch', 'English');

        // The core requirement: the translated amendment must reuse the existing motion translation's
        // wording wherever the amendment doesn't change anything, to minimize the diff between them.
        $this->assertStringContainsString('reuses the exact wording', $prompt);
        $this->assertStringContainsString('mirrors', $prompt);
    }

    public function testAmendmentSectionUserMessageIncludesAllThreeFragments(): void
    {
        $message = Prompts::amendmentSectionUserMessage(
            '<p>Original motion text</p>',
            '<p>Amended text</p>',
            '<p>Existing translation</p>'
        );

        $this->assertStringContainsString('<p>Original motion text</p>', $message);
        $this->assertStringContainsString('<p>Amended text</p>', $message);
        $this->assertStringContainsString('<p>Existing translation</p>', $message);
    }
}
