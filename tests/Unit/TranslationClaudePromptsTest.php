<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\plugins\translation_claude\Prompts;
use Tests\Support\Helper\TestBase;

/**
 * Covers plugins/translation_claude/Prompts.php: the exact wording is free to evolve, but the
 * structural guarantees the rest of the codebase (and the feature request) cares about must hold -
 * the HTML-preservation rules are always included, the tool schema is well-formed, the batch user
 * messages carry every task with the fields the tool schema/prompt promise, and the
 * amendment-specific instruction to stay close to the existing motion translation is present.
 */
class TranslationClaudePromptsTest extends TestBase
{
    public function testMotionSectionsPromptIncludesTheHtmlPreservationRulesAndTheTool(): void
    {
        $prompt = Prompts::motionSectionsBatchSystemPrompt();

        $this->assertStringContainsString('class', $prompt);
        $this->assertStringContainsString('whitespace', $prompt);
        $this->assertStringContainsString('HTML', $prompt);
        $this->assertStringContainsString(Prompts::TOOL_NAME, $prompt);
    }

    public function testAmendmentSectionsPromptIncludesTheHtmlPreservationRulesAndTheTool(): void
    {
        $prompt = Prompts::amendmentSectionsBatchSystemPrompt();

        $this->assertStringContainsString('class', $prompt);
        $this->assertStringContainsString('whitespace', $prompt);
        $this->assertStringContainsString(Prompts::TOOL_NAME, $prompt);
    }

    public function testAmendmentSectionsPromptInstructsStayingCloseToTheExistingTranslation(): void
    {
        $prompt = Prompts::amendmentSectionsBatchSystemPrompt();

        // The core requirement: the translated amendment must reuse the existing motion translation's
        // wording wherever the amendment doesn't change anything, to minimize the diff between them.
        $this->assertStringContainsString('reuses the exact wording', $prompt);
        $this->assertStringContainsString('mirrors', $prompt);
    }

    public function testMotionSectionsBatchUserMessageIncludesEveryTask(): void
    {
        $message = Prompts::motionSectionsBatchUserMessage([
            10 => ['sourceLanguage' => 'Deutsch', 'targetLanguage' => 'English', 'sourceHtml' => '<p>Erste Sektion</p>'],
            11 => ['sourceLanguage' => 'Deutsch', 'targetLanguage' => 'Français', 'sourceHtml' => '<p>Zweite Sektion</p>'],
        ]);

        $decoded = json_decode($message, true);
        $this->assertCount(2, $decoded['tasks']);
        $this->assertSame(10, $decoded['tasks'][0]['sectionId']);
        $this->assertSame('<p>Erste Sektion</p>', $decoded['tasks'][0]['sourceHtml']);
        $this->assertSame('English', $decoded['tasks'][0]['targetLanguage']);
        $this->assertSame(11, $decoded['tasks'][1]['sectionId']);
        $this->assertSame('Français', $decoded['tasks'][1]['targetLanguage']);
    }

    public function testAmendmentSectionsBatchUserMessageIncludesAllFragmentsPerTask(): void
    {
        $message = Prompts::amendmentSectionsBatchUserMessage([
            42 => [
                'sourceLanguage' => 'Deutsch',
                'targetLanguage' => 'English',
                'originalMotionHtml' => '<p>Original motion text</p>',
                'amendedHtml' => '<p>Amended text</p>',
                'existingMotionTranslationHtml' => '<p>Existing translation</p>',
            ],
        ]);

        $decoded = json_decode($message, true);
        $this->assertCount(1, $decoded['tasks']);
        $task = $decoded['tasks'][0];
        $this->assertSame(42, $task['sectionId']);
        $this->assertSame('<p>Original motion text</p>', $task['originalMotionHtml']);
        $this->assertSame('<p>Amended text</p>', $task['amendedHtml']);
        $this->assertSame('<p>Existing translation</p>', $task['existingMotionTranslationHtml']);
    }

    public function testToolSchemaIsWellFormed(): void
    {
        $schema = Prompts::translationsToolSchema();

        $this->assertSame(Prompts::TOOL_NAME, $schema['name']);
        $this->assertSame('object', $schema['input_schema']['type']);
        $this->assertSame('array', $schema['input_schema']['properties']['translations']['type']);
        $itemProperties = $schema['input_schema']['properties']['translations']['items']['properties'];
        $this->assertArrayHasKey('sectionId', $itemProperties);
        $this->assertArrayHasKey('translatedHtml', $itemProperties);
    }
}
