<?php

declare(strict_types=1);

namespace app\plugins\translation_claude;

/**
 * Default prompts for the Claude-backed translation plugin. Plain PHP, not a separate template file,
 * so they stay next to the code that uses them; edit these constants/methods directly to tune
 * translation behaviour for a specific installation - there is no code-free configuration mechanism
 * for this.
 *
 * Section content is always an HTML fragment (see models/sectionTypes/), so every prompt repeats the
 * same structural rules: preserve tags/attributes/classes and surrounding whitespace exactly, and
 * translate only the human-readable text.
 *
 * Both motion and amendment sections are translated in a single batched request per motion/amendment
 * (see SectionTranslator), covering every section/language pair that needs filling - not one request
 * per section - so every prompt here describes a *list* of independent translation tasks and asks for
 * a matching list back, via the same tool schema (translationsToolSchema()) regardless of whether the
 * tasks are for motion or amendment sections.
 */
class Prompts
{
    public const TOOL_NAME = 'provide_translations';

    private const HTML_RULES = <<<'TEXT'
        The text in each task is an HTML fragment from a motion/amendment management system. Follow
        these rules strictly for every task:
        - Preserve every HTML tag, attribute and attribute value exactly as given, including "class"
          attributes - never translate, remove, add, or reorder them.
        - Preserve the exact whitespace and line breaks immediately before and after each tag.
        - Only translate the human-readable text content between/around the tags.
        - Do not add any commentary, explanation, headers, or markdown code fences - a translated
          field must contain only the translated HTML fragment, and nothing else.
        TEXT;

    /**
     * The Anthropic tool ("function calling") definition used to force a structured, reliably
     * parseable response for a batch of translation tasks, regardless of how many sections/languages
     * are involved or whether they're motion or amendment sections.
     *
     * @return array<string, mixed>
     */
    public static function translationsToolSchema(): array
    {
        return [
            'name' => self::TOOL_NAME,
            'description' => 'Returns the translated HTML fragment for each requested translation task.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'translations' => [
                        'type' => 'array',
                        'description' => 'One entry per input task, in any order.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'sectionId' => [
                                    'type' => 'integer',
                                    'description' => 'The "sectionId" of the input task this translation belongs to.',
                                ],
                                'translatedHtml' => [
                                    'type' => 'string',
                                    'description' => 'The translated HTML fragment for this task.',
                                ],
                            ],
                            'required' => ['sectionId', 'translatedHtml'],
                        ],
                    ],
                ],
                'required' => ['translations'],
            ],
        ];
    }

    public static function motionSectionsBatchSystemPrompt(): string
    {
        $template = <<<'TEXT'
            You are a professional translator working for a political organization. The user message
            is a JSON object listing independent translation tasks; each task translates one HTML
            fragment ("sourceHtml") from one language ("sourceLanguage") to another
            ("targetLanguage") - the language pair can differ between tasks. For each task, translate
            the fragment, preserving the formal, precise register typical of motions and resolutions.

            %HTML_RULES%

            Use the %TOOL% tool to return your result.
            TEXT;

        return strtr($template, [
            '%HTML_RULES%' => self::HTML_RULES,
            '%TOOL%' => self::TOOL_NAME,
        ]);
    }

    /**
     * @param array<int, array{sourceLanguage: string, targetLanguage: string, sourceHtml: string}> $tasksBySectionId
     */
    public static function motionSectionsBatchUserMessage(array $tasksBySectionId): string
    {
        $tasks = [];
        foreach ($tasksBySectionId as $sectionId => $task) {
            $tasks[] = [
                'sectionId' => $sectionId,
                'sourceLanguage' => $task['sourceLanguage'],
                'targetLanguage' => $task['targetLanguage'],
                'sourceHtml' => $task['sourceHtml'],
            ];
        }

        return (string) json_encode(['tasks' => $tasks], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Extends the plain motion-sections prompt: an amendment's changed text must be translated so
     * that it stays as close as possible to the already-existing translation of the motion text it
     * amends, so the (translated) diff between motion and amendment mirrors the original-language one
     * instead of introducing translation-only noise (different wording for unchanged passages).
     */
    public static function amendmentSectionsBatchSystemPrompt(): string
    {
        $template = <<<'TEXT'
            You are a professional translator working for a political organization. The user message
            is a JSON object listing independent amendment-translation tasks; the language pair can
            differ between tasks. Each task provides:
            - "sourceLanguage" / "targetLanguage": the languages to translate from/to for this task.
            - "originalMotionHtml": the motion's text before the amendment, in sourceLanguage.
            - "amendedHtml": the same passage after the amendment, in sourceLanguage - this is the
              actual text to translate.
            - "existingMotionTranslationHtml": an already-published translation of
              "originalMotionHtml" into targetLanguage.

            For each task, compare "originalMotionHtml" and "amendedHtml" to identify exactly what the
            amendment changes. Then produce a targetLanguage translation of "amendedHtml" that:
            - reuses the exact wording of "existingMotionTranslationHtml" for every part "amendedHtml"
              did not change compared to "originalMotionHtml", so the difference between
              "existingMotionTranslationHtml" and your translation mirrors, as closely as possible,
              the difference between "originalMotionHtml" and "amendedHtml";
            - only introduces new wording for the part(s) the amendment actually changes;
            - reads as a natural, complete text in targetLanguage on its own, not as a diff or an
              excerpt.

            %HTML_RULES%

            Use the %TOOL% tool to return your result, with "translatedHtml" being the translation of
            each task's "amendedHtml".
            TEXT;

        return strtr($template, [
            '%HTML_RULES%' => self::HTML_RULES,
            '%TOOL%' => self::TOOL_NAME,
        ]);
    }

    /**
     * @param array<int, array{sourceLanguage: string, targetLanguage: string, originalMotionHtml: string, amendedHtml: string, existingMotionTranslationHtml: string}> $tasksBySectionId
     */
    public static function amendmentSectionsBatchUserMessage(array $tasksBySectionId): string
    {
        $tasks = [];
        foreach ($tasksBySectionId as $sectionId => $task) {
            $tasks[] = [
                'sectionId' => $sectionId,
                'sourceLanguage' => $task['sourceLanguage'],
                'targetLanguage' => $task['targetLanguage'],
                'originalMotionHtml' => $task['originalMotionHtml'],
                'amendedHtml' => $task['amendedHtml'],
                'existingMotionTranslationHtml' => $task['existingMotionTranslationHtml'],
            ];
        }

        return (string) json_encode(['tasks' => $tasks], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
