<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\motionTypeTemplates\{Application, Motion};
use app\models\sectionTypes\ISectionType;
use Codeception\Attribute\Group;
use app\models\db\{Consultation, ConsultationMotionType, ConsultationSettingsMotionSection, Site};
use Tests\Support\Helper\DBTestBase;

/**
 * Covers the motion type templates (models/motionTypeTemplates/*, backed by SectionTemplateBuilder):
 * on a site with several supported languages, a template's text-content sections (title, body, ...)
 * are created once per language and grouped via languageGrouping, while non-text sections (image,
 * tabular data, PDF, ...) stay a single, language-neutral section. On a single-language site, nothing
 * changes - every section stays language-neutral, exactly as before this feature existed.
 */
#[Group('database')]
class MotionTypeTemplateLanguageTest extends DBTestBase
{
    /** Consultation 1's wordingBase is "de-parteitag", so "de" is the primary language. */
    private static function setSiteSupportedLanguages(array $languages): Consultation
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);
        /** @var Site $site */
        $site = $consultation->site;

        $settings = $site->getSettings();
        $settings->supportedLanguages = $languages;
        $site->setSettings($settings);
        $site->save();

        $consultation->refresh();

        return $consultation;
    }

    /**
     * @return array<string, array{type: int, language: ?string, grouping: ?string}>
     */
    private static function sectionsByTitle(ConsultationMotionType $motionType): array
    {
        $motionType->refresh();

        $result = [];
        foreach ($motionType->motionSections as $section) {
            $key = $section->title . ($section->getLanguage() ?? '');
            $result[$key] = [
                'type'     => $section->type,
                'language' => $section->getLanguage(),
                'grouping' => $section->getLanguageGrouping(),
            ];
        }
        return $result;
    }

    public function testSingleLanguageSiteKeepsOneLanguageNeutralSectionPerSlot(): void
    {
        $consultation = self::setSiteSupportedLanguages([]);

        $motionType = Motion::doCreateMotionType($consultation);
        Motion::doCreateMotionSections($motionType);

        $motionType->refresh();
        $sections = $motionType->motionSections;

        $this->assertCount(3, $sections);
        foreach ($sections as $section) {
            $this->assertNull($section->getLanguage());
            $this->assertNull($section->getLanguageGrouping());
        }
        $this->assertSame([0, 1, 2], array_map(fn (ConsultationSettingsMotionSection $s) => $s->position, $sections));
    }

    public function testMultiLanguageSiteCreatesOneSectionPerLanguagePerSlot(): void
    {
        $consultation = self::setSiteSupportedLanguages(['de', 'en']);

        $motionType = Motion::doCreateMotionType($consultation);
        Motion::doCreateMotionSections($motionType);

        $motionType->refresh();
        $sections = $motionType->motionSections;

        // Title, text and reason, each in German and English.
        $this->assertCount(6, $sections);

        $byLanguageAndGrouping = array_map(
            fn (ConsultationSettingsMotionSection $s) => [$s->type, $s->getLanguage(), $s->getLanguageGrouping()],
            $sections
        );

        $this->assertContains([ISectionType::TYPE_TITLE, 'de', 'title'], $byLanguageAndGrouping);
        $this->assertContains([ISectionType::TYPE_TITLE, 'en', 'title'], $byLanguageAndGrouping);
        $this->assertContains([ISectionType::TYPE_TEXT_SIMPLE, 'de', 'text'], $byLanguageAndGrouping);
        $this->assertContains([ISectionType::TYPE_TEXT_SIMPLE, 'en', 'text'], $byLanguageAndGrouping);
        $this->assertContains([ISectionType::TYPE_TEXT_SIMPLE, 'de', 'reason'], $byLanguageAndGrouping);
        $this->assertContains([ISectionType::TYPE_TEXT_SIMPLE, 'en', 'reason'], $byLanguageAndGrouping);

        // Positions are sequential and, within a group, the primary language (de) comes first, so a
        // German reader sees title/text/reason in the same left-to-right order as on a single-language
        // site once the reading path filters down to their language.
        usort($sections, fn ($a, $b) => $a->position <=> $b->position);
        $this->assertSame(
            ['de', 'en', 'de', 'en', 'de', 'en'],
            array_map(fn (ConsultationSettingsMotionSection $s) => $s->getLanguage(), $sections)
        );
    }

    public function testSectionTitlesAreResolvedInTheSectionsOwnLanguage(): void
    {
        $consultation = self::setSiteSupportedLanguages(['de', 'en']);

        $motionType = Motion::doCreateMotionType($consultation);
        Motion::doCreateMotionSections($motionType);

        $motionType->refresh();

        $titlesByLanguage = [];
        foreach ($motionType->motionSections as $section) {
            if ($section->getLanguageGrouping() === 'reason') {
                $titlesByLanguage[$section->getLanguage()] = $section->title;
            }
        }

        // Each section's own label is translated into that section's language, not into whichever
        // language the admin performing the creation happens to be browsing in.
        $this->assertSame('Begründung', $titlesByLanguage['de']);
        $this->assertSame('Reason', $titlesByLanguage['en']);
    }

    public function testMultiLanguageSiteOnlyDuplicatesTextContentSections(): void
    {
        $consultation = self::setSiteSupportedLanguages(['de', 'en']);

        $motionType = Application::doCreateApplicationType($consultation);
        Application::doCreateApplicationSections($motionType);

        $sections = self::sectionsByTitle($motionType);

        // Name and intro are free text - one section per language.
        $nameCount  = count(array_filter($sections, fn ($s) => $s['type'] === ISectionType::TYPE_TITLE));
        $introCount = count(array_filter($sections, fn ($s) => $s['type'] === ISectionType::TYPE_TEXT_SIMPLE));
        $this->assertSame(2, $nameCount);
        $this->assertSame(2, $introCount);

        // Photo, data and signature are upload/structured-value sections - a single, language-neutral
        // section each, not duplicated.
        $imageSections   = array_filter($sections, fn ($s) => $s['type'] === ISectionType::TYPE_IMAGE);
        $tabularSections = array_filter($sections, fn ($s) => $s['type'] === ISectionType::TYPE_TABULAR);
        $this->assertCount(2, $imageSections);
        $this->assertCount(1, $tabularSections);
        foreach ([...$imageSections, ...$tabularSections] as $section) {
            $this->assertNull($section['language']);
            $this->assertNull($section['grouping']);
        }
    }
}
