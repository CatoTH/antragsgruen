<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\motionTypeTemplates\Motion;
use app\models\sectionTypes\ISectionType;
use Codeception\Attribute\Group;
use app\models\db\{Consultation, ConsultationMotionType, ConsultationSettingsMotionSection};
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class ConsultationMotionTypeLanguageTest extends DBTestBase
{
    /**
     * @param ConsultationSettingsMotionSection[] $sections
     */
    private static function createDummyMotionType(array $sections): ConsultationMotionType
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        $type = Motion::doCreateMotionType($consultation);
        foreach ($sections as $section) {
            $type->link('motionSections', $section);
        }

        return $type;
    }

    private static function createDummySection(int $id, int $type, string $title, ?string $language, ?string $languageGrouping = null): ConsultationSettingsMotionSection
    {
        $section        = new ConsultationSettingsMotionSection();
        $section->id    = $id;
        $section->title = $title;
        $section->type  = $type;
        $section->status = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->required = ConsultationSettingsMotionSection::REQUIRED_NO;

        $section->position      = 0;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
        $section->hasAmendments = 0;
        $section->positionRight = 0;

        $settings                   = $section->getSettingsObj();
        $settings->language         = $language;
        $settings->languageGrouping = $languageGrouping;
        $section->setSettingsObj($settings);

        return $section;
    }

    public function testMotionTypeWithoutAnyLanguageIsAvailableEverywhere(): void
    {
        $motionType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Title', null),
            self::createDummySection(1001, ISectionType::TYPE_TEXT_SIMPLE, 'Text', null),
        ]);

        $this->assertSame([], $motionType->getDefinedSectionLanguages());
        $this->assertTrue($motionType->isAvailableInLanguage('de'));
        $this->assertTrue($motionType->isAvailableInLanguage('en'));
    }

    public function testMotionTypeIsOnlyAvailableInItsDefinedLanguages(): void
    {
        $motionType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Titel', 'de', 'title'),
            self::createDummySection(1001, ISectionType::TYPE_TITLE, 'Title', 'en', 'title'),
        ]);

        $this->assertSame(['de', 'en'], $motionType->getDefinedSectionLanguages());
        $this->assertTrue($motionType->isAvailableInLanguage('de'));
        $this->assertTrue($motionType->isAvailableInLanguage('en'));
        $this->assertFalse($motionType->isAvailableInLanguage('nl'));
    }

    public function testSectionsForLanguageIncludeLanguageNeutralOnes(): void
    {
        $motionType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Titel', 'de', 'title'),
            self::createDummySection(1001, ISectionType::TYPE_TITLE, 'Title', 'en', 'title'),
            self::createDummySection(1002, ISectionType::TYPE_TEXT_SIMPLE, 'Reason', null),
        ]);

        $deSections = array_map(fn (ConsultationSettingsMotionSection $s) => $s->id, $motionType->getMotionSectionsForLanguage('de'));
        $enSections = array_map(fn (ConsultationSettingsMotionSection $s) => $s->id, $motionType->getMotionSectionsForLanguage('en'));

        $this->assertSame([1000, 1002], $deSections);
        $this->assertSame([1001, 1002], $enSections);
    }

    public function testNoWarningsForACleanSetup(): void
    {
        $motionType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Titel', 'de', 'title'),
            self::createDummySection(1001, ISectionType::TYPE_TITLE, 'Title', 'en', 'title'),
            self::createDummySection(1002, ISectionType::TYPE_TEXT_SIMPLE, 'Reason', null),
        ]);

        $this->assertSame([], $motionType->getLanguageSetupWarnings());
    }

    public function testWarnsAboutALanguageWithoutAGroup(): void
    {
        $motionType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Titel', 'de', null),
        ]);

        $warnings = $motionType->getLanguageSetupWarnings();
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Titel', $warnings[0]);
    }

    public function testWarnsAboutDuplicateLanguagesInTheSameGroup(): void
    {
        $motionType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Titel 1', 'de', 'title'),
            self::createDummySection(1001, ISectionType::TYPE_TITLE, 'Titel 2', 'de', 'title'),
        ]);

        $warnings = $motionType->getLanguageSetupWarnings();
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('title', $warnings[0]);
    }

    public function testWarnsAboutMixedTypesInTheSameGroup(): void
    {
        $motionType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Titel', 'de', 'body'),
            self::createDummySection(1001, ISectionType::TYPE_TEXT_SIMPLE, 'Text', 'en', 'body'),
        ]);

        $warnings = $motionType->getLanguageSetupWarnings();
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('body', $warnings[0]);
    }

    public function testGroupWithoutAnyLanguageIsNotWarnedAbout(): void
    {
        // A grouping without a language on either section is unusual but harmless: there is no
        // target language that could be duplicated.
        $motionType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Title A', null, 'body'),
            self::createDummySection(1001, ISectionType::TYPE_TITLE, 'Title B', null, 'body'),
        ]);

        $this->assertSame([], $motionType->getLanguageSetupWarnings());
    }
}
