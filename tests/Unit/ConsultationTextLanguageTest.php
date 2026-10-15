<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\db\{Consultation, ConsultationText};
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class ConsultationTextLanguageTest extends DBTestBase
{
    private static function createText(?string $language, string $text, string $textId = 'testpage'): ConsultationText
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        $page = new ConsultationText();
        $page->category = ConsultationText::DEFAULT_CATEGORY;
        $page->textId = $textId;
        $page->language = $language;
        $page->consultationId = $consultation->id;
        $page->siteId = $consultation->siteId;
        $page->title = 'Title ' . $text;
        $page->text = $text;
        $page->menuPosition = null;
        $page->editDate = date('Y-m-d H:i:s');
        $page->save();

        return $page;
    }

    public function testFilterPrefersLanguageSpecificVariant(): void
    {
        $fallback = self::createText(null, 'fallback');
        $german = self::createText('de', 'deutsch');
        $french = self::createText('fr', 'francais');

        $filtered = ConsultationText::filterForLanguage([$fallback, $german, $french], 'de');
        $this->assertCount(1, $filtered);
        $this->assertSame('deutsch', $filtered[0]->text);

        // The order of the input does not matter
        $filtered = ConsultationText::filterForLanguage([$german, $fallback, $french], 'de');
        $this->assertSame('deutsch', $filtered[0]->text);
    }

    public function testFilterFallsBackToLanguageIndependentVariant(): void
    {
        $fallback = self::createText(null, 'fallback');
        $german = self::createText('de', 'deutsch');

        $filtered = ConsultationText::filterForLanguage([$fallback, $german], 'en');
        $this->assertCount(1, $filtered);
        $this->assertSame('fallback', $filtered[0]->text);

        // Single-language mode: only language-independent texts are considered
        $filtered = ConsultationText::filterForLanguage([$german, $fallback], null);
        $this->assertSame('fallback', $filtered[0]->text);
    }

    public function testFilterDropsPagesOnlyExistingInOtherLanguages(): void
    {
        $german = self::createText('de', 'deutsch');
        $other = self::createText(null, 'other', 'otherpage');

        $filtered = ConsultationText::filterForLanguage([$german, $other], 'en');
        $this->assertCount(1, $filtered);
        $this->assertSame('otherpage', $filtered[0]->textId);
    }

    public function testVariantForLanguageCreatesCopyAndReusesExisting(): void
    {
        $fallback = self::createText(null, 'fallback');
        $german = self::createText('de', 'deutsch');

        $this->assertSame($fallback, $fallback->getVariantForLanguage(null));
        $this->assertSame($german->id, $fallback->getVariantForLanguage('de')->id);

        $english = $fallback->getVariantForLanguage('en');
        $this->assertNull($english->id);
        $this->assertSame('en', $english->language);
        $this->assertSame('fallback', $english->text);
        $this->assertSame($fallback->consultationId, $english->consultationId);
        $this->assertCount(2, $english->getOtherLanguageVariants());
    }

    public function testSharedSettingsAreSyncedToAllVariants(): void
    {
        $fallback = self::createText(null, 'fallback');
        $german = self::createText('de', 'deutsch');
        $variants = $german->getOtherLanguageVariants();
        $this->assertCount(1, $variants);

        $german->textId = 'renamed';
        $german->menuPosition = 1;
        $german->save();
        $german->syncSharedSettingsToVariants($variants);

        $fallback->refresh();
        $this->assertSame('renamed', $fallback->textId);
        $this->assertSame(1, $fallback->menuPosition);
        $this->assertSame('fallback', $fallback->text);
    }
}
