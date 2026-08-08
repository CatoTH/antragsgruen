<?php

declare(strict_types=1);

namespace app\plugins\translation_claude;

/**
 * Default prompts for the Claude-backed translation plugin. Plain PHP, not a separate template file,
 * so they stay next to the code that uses them; edit these constants directly to tune translation
 * behaviour for a specific installation - there is no code-free configuration mechanism for this.
 *
 * Section content is always an HTML fragment (see models/sectionTypes/), so every prompt repeats the
 * same structural rules: preserve tags/attributes/classes and surrounding whitespace exactly, and
 * translate only the human-readable text.
 */
class Prompts
{
    private const HTML_RULES = <<<'TEXT'
        The text you are given is an HTML fragment from a motion/amendment management system. Follow
        these rules strictly:
        - Preserve every HTML tag, attribute and attribute value exactly as given, including "class"
          attributes - never translate, remove, add, or reorder them.
        - Preserve the exact whitespace and line breaks immediately before and after each tag.
        - Only translate the human-readable text content between/around the tags.
        - Do not add any commentary, explanation, headers, or markdown code fences - your entire
          reply must be the translated HTML fragment, and nothing else.
        TEXT;

    public static function motionSectionSystemPrompt(string $sourceLanguage, string $targetLanguage): string
    {
        $template = <<<'TEXT'
            You are a professional translator working for a political organization. Translate the
            HTML fragment the user gives you from %SOURCE% to %TARGET%, preserving the formal,
            precise register typical of motions and resolutions.

            %HTML_RULES%
            TEXT;

        return strtr($template, [
            '%SOURCE%' => $sourceLanguage,
            '%TARGET%' => $targetLanguage,
            '%HTML_RULES%' => self::HTML_RULES,
        ]);
    }

    /**
     * Extends the plain motion-section prompt: an amendment's changed text must be translated so
     * that it stays as close as possible to the already-existing translation of the motion text it
     * amends, so the (translated) diff between motion and amendment mirrors the original-language
     * one instead of introducing translation-only noise (different wording for unchanged passages).
     */
    public static function amendmentSectionSystemPrompt(string $sourceLanguage, string $targetLanguage): string
    {
        $template = <<<'TEXT'
            You are a professional translator working for a political organization. You translate
            amendments (proposed changes to a motion) from %SOURCE% to %TARGET%, preserving the
            formal, precise register typical of motions and resolutions.

            You will be given three HTML fragments:
            1. ORIGINAL MOTION TEXT (%SOURCE%): the motion's text before the amendment.
            2. AMENDED TEXT (%SOURCE%): the same passage after the amendment - this is your
               translation input.
            3. EXISTING TRANSLATION OF THE MOTION TEXT (%TARGET%): an already-published translation
               of fragment 1 into %TARGET%.

            Compare fragment 1 and fragment 2 to identify exactly what the amendment changes. Then
            produce a %TARGET% translation of fragment 2 (the amended text) that:
            - reuses the exact wording of fragment 3 for every part fragment 2 did not change
              compared to fragment 1, so the difference between fragment 3 and your translation
              mirrors, as closely as possible, the difference between fragment 1 and fragment 2;
            - only introduces new wording for the part(s) the amendment actually changes;
            - reads as a natural, complete %TARGET% text on its own, not as a diff or an excerpt.

            %HTML_RULES%
            TEXT;

        return strtr($template, [
            '%SOURCE%' => $sourceLanguage,
            '%TARGET%' => $targetLanguage,
            '%HTML_RULES%' => self::HTML_RULES,
        ]);
    }

    public static function amendmentSectionUserMessage(
        string $originalMotionText,
        string $amendedText,
        string $existingMotionTranslation
    ): string {
        $template = <<<'TEXT'
            ORIGINAL MOTION TEXT:
            %ORIGINAL%

            AMENDED TEXT:
            %AMENDED%

            EXISTING TRANSLATION OF THE MOTION TEXT:
            %EXISTING_TRANSLATION%
            TEXT;

        return strtr($template, [
            '%ORIGINAL%' => $originalMotionText,
            '%AMENDED%' => $amendedText,
            '%EXISTING_TRANSLATION%' => $existingMotionTranslation,
        ]);
    }
}
