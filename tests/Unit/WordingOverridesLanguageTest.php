<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\LanguageTools;
use app\models\db\{Consultation, ConsultationText, Site};
use app\models\forms\WordingOverridesForm;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

/**
 * Consultation 1's wordingBase is "de-parteitag", so "de" is the primary language.
 */
#[Group('database')]
class WordingOverridesLanguageTest extends DBTestBase
{
    private const CATEGORY = 'motion';

    private static function getConsultation(array $supportedLanguages): Consultation
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);
        /** @var Site $site */
        $site = $consultation->site;

        $settings = $site->getSettings();
        $settings->supportedLanguages = $supportedLanguages;
        $site->setSettings($settings);
        $site->save();

        foreach ($consultation->texts as $text) {
            if ($text->category === self::CATEGORY) {
                $text->delete();
            }
        }
        $consultation->refresh();

        return $consultation;
    }

    private static function createText(Consultation $consultation, string $textId, ?string $language, string $text): void
    {
        $row = new ConsultationText();
        $row->consultationId = $consultation->id;
        $row->category = self::CATEGORY;
        $row->textId = $textId;
        $row->language = $language;
        $row->text = $text;
        $row->editDate = date('Y-m-d H:i:s');
        $row->save();
    }

    private static function getOverrides(Consultation $consultation, string $language): array
    {
        $consultation->refresh();
        return ConsultationText::getWordingOverrides($consultation->texts, $consultation, $language, self::CATEGORY);
    }

    /**
     * @return array<string, string> "textId/language" => text
     */
    private static function getStoredRows(Consultation $consultation): array
    {
        $consultation->refresh();
        $rows = [];
        foreach ($consultation->texts as $text) {
            if ($text->category === self::CATEGORY) {
                $rows[$text->textId . '/' . ($text->language ?? 'NULL')] = $text->text;
            }
        }
        ksort($rows);
        return $rows;
    }

    private static function saveInLanguage(Consultation $consultation, string $language, string $textId, string $value): void
    {
        $consultation->refresh();
        LanguageTools::renderInLanguage($consultation, $language, function () use ($consultation, $textId, $value) {
            $form = new WordingOverridesForm($consultation, $consultation->texts, ['consultationId' => $consultation->id]);
            $form->save(self::CATEGORY, $textId, $value);
        });
    }

    public function testSingleLanguageSiteOnlyUsesLanguageIndependentRows(): void
    {
        $consultation = self::getConsultation([]);
        self::createText($consultation, 'a', null, 'neutral');
        self::createText($consultation, 'a', 'de', 'deutsch');
        self::createText($consultation, 'b', 'de', 'deutsch');

        $this->assertSame(['a' => 'neutral'], self::getOverrides($consultation, 'de'));
    }

    public function testPrimaryLanguageFallsBackToLanguageIndependentRows(): void
    {
        $consultation = self::getConsultation(['de', 'en']);
        self::createText($consultation, 'a', 'de', 'deutsch');
        self::createText($consultation, 'a', null, 'neutral');
        self::createText($consultation, 'b', null, 'neutral');
        self::createText($consultation, 'c', null, 'neutral');
        self::createText($consultation, 'c', 'de', '');

        $this->assertSame(['a' => 'deutsch', 'b' => 'neutral'], self::getOverrides($consultation, 'de'));
        $this->assertSame(['a' => 'deutsch', 'b' => 'neutral'], self::getOverrides($consultation, 'de-parteitag'));
    }

    public function testOtherLanguagesIgnoreLanguageIndependentRows(): void
    {
        $consultation = self::getConsultation(['de', 'en']);
        self::createText($consultation, 'a', null, 'neutral');
        self::createText($consultation, 'b', 'en', 'english');

        $this->assertSame(['b' => 'english'], self::getOverrides($consultation, 'en'));
    }

    public function testSingleLanguageSaveWritesAndDeletesLanguageIndependentRows(): void
    {
        $consultation = self::getConsultation([]);

        self::saveInLanguage($consultation, 'de', 'a', 'neutral');
        $this->assertSame(['a/NULL' => 'neutral'], self::getStoredRows($consultation));

        self::saveInLanguage($consultation, 'de', 'a', '');
        $this->assertSame([], self::getStoredRows($consultation));
    }

    public function testUnchangedInheritedValueIsNotCopied(): void
    {
        $consultation = self::getConsultation(['de', 'en']);
        self::createText($consultation, 'a', null, 'neutral');

        self::saveInLanguage($consultation, 'de', 'a', 'neutral');
        $this->assertSame(['a/NULL' => 'neutral'], self::getStoredRows($consultation));
    }

    public function testChangedValueIsWrittenForCurrentLanguage(): void
    {
        $consultation = self::getConsultation(['de', 'en']);
        self::createText($consultation, 'a', null, 'neutral');

        self::saveInLanguage($consultation, 'de', 'a', 'deutsch');
        self::saveInLanguage($consultation, 'en', 'b', 'english');
        $this->assertSame([
            'a/NULL' => 'neutral',
            'a/de'   => 'deutsch',
            'b/en'   => 'english',
        ], self::getStoredRows($consultation));
    }

    public function testClearingBlocksFallbackOrDeletesRow(): void
    {
        $consultation = self::getConsultation(['de', 'en']);
        self::createText($consultation, 'a', null, 'neutral');
        self::createText($consultation, 'b', 'en', 'english');

        // An inherited value can only be removed by an empty row for this language
        self::saveInLanguage($consultation, 'de', 'a', '');
        // Without anything to inherit, clearing simply deletes the row
        self::saveInLanguage($consultation, 'en', 'b', '');

        $this->assertSame(['a/NULL' => 'neutral', 'a/de' => ''], self::getStoredRows($consultation));
        $this->assertSame([], self::getOverrides($consultation, 'de'));
    }
}
