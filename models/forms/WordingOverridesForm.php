<?php

declare(strict_types=1);

namespace app\models\forms;

use app\components\{HTMLTools, LanguageTools};
use app\models\db\{Consultation, ConsultationText};

/**
 * Reads and writes the UI wording overrides of a consultation or a motion type, as edited on the
 * "Translation / Wording" admin pages. See ConsultationText::getWordingOverrides() for the language rules.
 *
 * On multi-language sites, edits are always stored for the admin's current language. Only fields that
 * actually changed are written, so values inherited from language-independent rows stay inherited.
 */
class WordingOverridesForm
{
    /**
     * @param ConsultationText[] $existingTexts all stored wording overrides of this scope
     * @param array<string, int|null> $scope attributes identifying new rows
     */
    public function __construct(
        private readonly Consultation $consultation,
        private array $existingTexts,
        private readonly array $scope,
    ) {
    }

    /**
     * @return array<string, string> textId => the override currently in effect for the admin's language
     */
    public function getDisplayValues(string $category): array
    {
        return ConsultationText::getWordingOverrides($this->existingTexts, $this->consultation, LanguageTools::getCurrentLanguage(), $category);
    }

    public function save(string $category, string $textId, string $value): void
    {
        $value = ($value === '' ? '' : HTMLTools::cleanHtmlTranslationString($value));
        if (($this->getDisplayValues($category)[$textId] ?? '') === $value) {
            return;
        }

        $language = $this->getStorageLanguage();
        $text = $this->findText($category, $textId, $language);

        // Without a fallback to block, an empty value just means "no override"
        if ($value === '' && !($language !== null && $this->hasLanguageIndependentFallback($category, $textId, $language))) {
            $text?->delete();
            return;
        }

        if (!$text) {
            $text = new ConsultationText();
            $text->setAttributes($this->scope, false);
            $text->category = $category;
            $text->textId = $textId;
            $text->language = $language;
            $this->existingTexts[] = $text;
        }
        $text->text = $value;
        $text->editDate = date('Y-m-d H:i:s');
        $text->save();
    }

    private function getStorageLanguage(): ?string
    {
        if (LanguageTools::isMultiLanguageSite($this->consultation->site)) {
            return LanguageTools::getCurrentLanguage();
        }
        return null;
    }

    private function findText(string $category, string $textId, ?string $language): ?ConsultationText
    {
        foreach ($this->existingTexts as $text) {
            if ($text->category === $category && $text->textId === $textId && $text->language === $language) {
                return $text;
            }
        }
        return null;
    }

    private function hasLanguageIndependentFallback(string $category, string $textId, string $language): bool
    {
        if ($language !== LanguageTools::getPrimaryLanguage($this->consultation)) {
            return false;
        }
        $fallback = $this->findText($category, $textId, null);

        return $fallback !== null && $fallback->text !== '';
    }
}
