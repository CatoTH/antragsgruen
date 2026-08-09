<?php

declare(strict_types=1);

namespace app\plugins\translation_claude;

use app\components\LanguageTools;
use app\models\db\{Amendment, AmendmentSection, Consultation, IMotionSection, Motion, MotionSection};

/**
 * Builds one batched translation request per motion/amendment - covering every section
 * SectionAutofill found empty, not one request per section - and calls Claude for it. Split out from
 * the static Module hooks (ModuleBase requires those to be static) purely so it can be unit-tested
 * with an injected ClaudeClient instead of making real API calls.
 */
class SectionTranslator
{
    private ClaudeClient $client;

    public function __construct(Credentials $credentials, ?ClaudeClient $client = null)
    {
        $this->client = $client ?? new ClaudeClient($credentials);
    }

    /**
     * @param MotionSection[] $sections the sections to (try to) translate - already known to be empty
     * @return array<int, string> sectionId => translated content, only for sections a source could be
     *         found for; omitted sections simply couldn't be translated (no grouping, no source
     *         section with content, ...)
     */
    public function translateMotionSections(Motion $motion, array $sections): array
    {
        $consultation = $motion->getMyConsultation();
        $allSections = $motion->getActiveSections();

        $tasks = [];
        foreach ($sections as $section) {
            $targetLanguage = $section->getSettings()?->getLanguage();
            $grouping = $section->getSettings()?->getLanguageGrouping();
            if ($targetLanguage === null || $grouping === null) {
                continue;
            }

            $source = self::findTranslationSource($allSections, $section, $grouping, $consultation);
            $sourceLanguage = $source?->getSettings()?->getLanguage();
            if ($source === null || $sourceLanguage === null) {
                continue;
            }

            $tasks[$section->sectionId] = [
                'sourceLanguage' => LanguageTools::getLanguageName($sourceLanguage),
                'targetLanguage' => LanguageTools::getLanguageName($targetLanguage),
                'sourceHtml' => $source->getData(),
            ];
        }

        if (count($tasks) === 0) {
            return [];
        }

        $result = $this->client->sendStructuredMessage(
            Prompts::motionSectionsBatchSystemPrompt(),
            Prompts::motionSectionsBatchUserMessage($tasks),
            Prompts::translationsToolSchema()
        );

        return self::parseBatchResult($result);
    }

    /**
     * @param AmendmentSection[] $sections
     * @return array<int, string>
     */
    public function translateAmendmentSections(Amendment $amendment, array $sections): array
    {
        $consultation = $amendment->getMyConsultation();
        $allSections = $amendment->getActiveSections();

        $tasks = [];
        foreach ($sections as $section) {
            $targetLanguage = $section->getSettings()?->getLanguage();
            $grouping = $section->getSettings()?->getLanguageGrouping();
            if ($targetLanguage === null || $grouping === null) {
                continue;
            }

            $targetMotionOriginal = $section->getOriginalMotionSection();
            if ($targetMotionOriginal === null) {
                continue;
            }

            $sourceSection = self::findTranslationSource($allSections, $section, $grouping, $consultation);
            $sourceLanguage = $sourceSection?->getSettings()?->getLanguage();
            if ($sourceSection === null || $sourceLanguage === null) {
                continue;
            }
            /** @var AmendmentSection $sourceSection */
            $sourceMotionOriginal = $sourceSection->getOriginalMotionSection();
            if ($sourceMotionOriginal === null) {
                continue;
            }

            $tasks[$section->sectionId] = [
                'sourceLanguage' => LanguageTools::getLanguageName($sourceLanguage),
                'targetLanguage' => LanguageTools::getLanguageName($targetLanguage),
                'originalMotionHtml' => $sourceMotionOriginal->getData(),
                'amendedHtml' => $sourceSection->getData(),
                'existingMotionTranslationHtml' => $targetMotionOriginal->getData(),
            ];
        }

        if (count($tasks) === 0) {
            return [];
        }

        $result = $this->client->sendStructuredMessage(
            Prompts::amendmentSectionsBatchSystemPrompt(),
            Prompts::amendmentSectionsBatchUserMessage($tasks),
            Prompts::translationsToolSchema()
        );

        return self::parseBatchResult($result);
    }

    /**
     * @param array<string, mixed>|null $toolResult the already-decoded tool_use "input", or null on
     *        any request failure (see ClaudeClient::sendStructuredMessage())
     * @return array<int, string>
     */
    private static function parseBatchResult(?array $toolResult): array
    {
        $result = [];
        foreach (($toolResult['translations'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $sectionId = $entry['sectionId'] ?? null;
            $translatedHtml = $entry['translatedHtml'] ?? null;
            if (is_int($sectionId) && is_string($translatedHtml) && trim($translatedHtml) !== '') {
                $result[$sectionId] = $translatedHtml;
            }
        }

        return $result;
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
