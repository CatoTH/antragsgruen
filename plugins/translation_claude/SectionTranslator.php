<?php

declare(strict_types=1);

namespace app\plugins\translation_claude;

use app\components\LanguageTools;
use app\models\db\{Amendment, AmendmentSection, Consultation, IMotionSection, Motion, MotionSection};

/**
 * Finds a suitable source section to translate from and calls Claude for it. Split out from the
 * static Module hooks (ModuleBase requires those to be static) purely so it can be unit-tested with
 * an injected ClaudeClient instead of making real API calls.
 */
class SectionTranslator
{
    private ClaudeClient $client;

    public function __construct(Credentials $credentials, ?ClaudeClient $client = null)
    {
        $this->client = $client ?? new ClaudeClient($credentials);
    }

    public function translateMotionSection(Motion $motion, MotionSection $section): ?string
    {
        $targetLanguage = $section->getSettings()?->getLanguage();
        $grouping = $section->getSettings()?->getLanguageGrouping();
        if ($targetLanguage === null || $grouping === null) {
            return null;
        }

        /** @var MotionSection|null $source */
        $source = self::findTranslationSource($motion->getActiveSections(), $section, $grouping, $motion->getMyConsultation());
        if ($source === null) {
            return null;
        }
        $sourceLanguage = $source->getSettings()?->getLanguage();
        if ($sourceLanguage === null) {
            return null;
        }

        $systemPrompt = Prompts::motionSectionSystemPrompt(
            LanguageTools::getLanguageName($sourceLanguage),
            LanguageTools::getLanguageName($targetLanguage)
        );

        return $this->client->sendMessage($systemPrompt, $source->getData());
    }

    public function translateAmendmentSection(Amendment $amendment, AmendmentSection $section): ?string
    {
        $targetLanguage = $section->getSettings()?->getLanguage();
        $grouping = $section->getSettings()?->getLanguageGrouping();
        if ($targetLanguage === null || $grouping === null) {
            return null;
        }

        $targetMotionOriginal = $section->getOriginalMotionSection();
        if ($targetMotionOriginal === null) {
            return null;
        }

        /** @var AmendmentSection|null $sourceSection */
        $sourceSection = self::findTranslationSource($amendment->getActiveSections(), $section, $grouping, $amendment->getMyConsultation());
        if ($sourceSection === null) {
            return null;
        }
        $sourceLanguage = $sourceSection->getSettings()?->getLanguage();
        if ($sourceLanguage === null) {
            return null;
        }
        $sourceMotionOriginal = $sourceSection->getOriginalMotionSection();
        if ($sourceMotionOriginal === null) {
            return null;
        }

        $sourceLanguageName = LanguageTools::getLanguageName($sourceLanguage);
        $targetLanguageName = LanguageTools::getLanguageName($targetLanguage);

        $systemPrompt = Prompts::amendmentSectionSystemPrompt($sourceLanguageName, $targetLanguageName);
        $userMessage = Prompts::amendmentSectionUserMessage(
            $sourceMotionOriginal->getData(),
            $sourceSection->getData(),
            $targetMotionOriginal->getData()
        );

        return $this->client->sendMessage($systemPrompt, $userMessage);
    }

    /**
     * Among $sections (siblings of $target within the same motion/amendment), finds the best section
     * sharing $target's languageGrouping to translate from: one that actually has content
     * (IMotionSection::hasContentForFiltering() - for an amendment section this correctly means "has
     * a real change", not raw non-emptiness, see AmendmentSection::hasContentForFiltering()),
     * preferring the consultation's primary language, else the first one found with content.
     *
     * @param IMotionSection[] $sections
     */
    private static function findTranslationSource(array $sections, IMotionSection $target, string $grouping, ?Consultation $consultation): ?IMotionSection
    {
        $candidates = [];
        foreach ($sections as $section) {
            if ($section === $target) {
                continue;
            }
            if ($section->getSettings()?->getLanguageGrouping() !== $grouping) {
                continue;
            }
            if (!$section->hasContentForFiltering()) {
                continue;
            }
            $candidates[] = $section;
        }

        if (count($candidates) === 0) {
            return null;
        }

        $primaryLanguage = LanguageTools::getPrimaryLanguage($consultation);
        foreach ($candidates as $candidate) {
            if ($candidate->getSettings()?->getLanguage() === $primaryLanguage) {
                return $candidate;
            }
        }

        return $candidates[0];
    }
}
