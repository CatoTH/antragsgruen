<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\MotionSectionLanguageFilter;
use app\models\motionTypeTemplates\Motion as MotionTemplate;
use app\models\sectionTypes\ISectionType;
use app\models\settings\MotionSection as MotionSectionSettings;
use Codeception\Attribute\Group;
use app\models\db\{AmendmentSection, Consultation, ConsultationMotionType, ConsultationSettingsMotionSection, Motion, MotionSection};
use Tests\Support\Helper\DBTestBase;

/**
 * Covers the D4 fallback logic (components/MotionSectionLanguageFilter.php) that decides which of a
 * motion's parallel per-language sections a reader sees, plus the amendment-specific "did this
 * language actually change" override (AmendmentSection::hasContentForFiltering()) that logic relies
 * on. DB-backed because ConsultationSettingsMotionSection/MotionSection are ActiveRecord classes -
 * even reading a property requires a DB schema lookup, see the deviation note in
 * multilanguage-implementation.md.
 */
#[Group('database')]
class MotionSectionLanguageFilterTest extends DBTestBase
{
    private static function createSectionType(int $id, int $type, string $title, ?string $language, ?string $languageGrouping = null): ConsultationSettingsMotionSection
    {
        $section            = new ConsultationSettingsMotionSection();
        $section->id        = $id;
        $section->title     = $title;
        $section->type      = $type;
        $section->status    = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->required  = ConsultationSettingsMotionSection::REQUIRED_NO;
        $section->position      = 0;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
        $section->hasAmendments = 1;
        $section->positionRight = 0;

        $settings                   = $section->getSettingsObj();
        $settings->language         = $language;
        $settings->languageGrouping = $languageGrouping;
        $section->setSettingsObj($settings);

        return $section;
    }

    /**
     * A motion type with: a title (de/en, grouped), a body (de/en, grouped) and a language-neutral
     * reason.
     */
    private static function createMotionType(): ConsultationMotionType
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);
        $motionType   = MotionTemplate::doCreateMotionType($consultation);

        $motionType->link('motionSections', self::createSectionType(2100, ISectionType::TYPE_TITLE, 'Titel', 'de', 'title'));
        $motionType->link('motionSections', self::createSectionType(2101, ISectionType::TYPE_TITLE, 'Title', 'en', 'title'));
        $motionType->link('motionSections', self::createSectionType(2102, ISectionType::TYPE_TEXT_SIMPLE, 'Antragstext', 'de', 'body'));
        $motionType->link('motionSections', self::createSectionType(2103, ISectionType::TYPE_TEXT_SIMPLE, 'Motion text', 'en', 'body'));
        $motionType->link('motionSections', self::createSectionType(2104, ISectionType::TYPE_TEXT_SIMPLE, 'Begründung', null));

        return $motionType;
    }

    private static function createMotion(ConsultationMotionType $motionType): Motion
    {
        $motion                          = new Motion();
        $motion->motionTypeId             = $motionType->id;
        $motion->consultationId           = $motionType->consultationId;
        $motion->title                    = '';
        $motion->titlePrefix              = '';
        $motion->version                  = Motion::VERSION_DEFAULT;
        $motion->status                   = Motion::STATUS_DRAFT;
        $motion->dateCreation             = date('Y-m-d H:i:s');
        $motion->dateContentModification  = date('Y-m-d H:i:s');
        $motion->cache                    = '';
        $motion->save();

        return $motion;
    }

    private static function saveSection(Motion $motion, int $sectionId, string $data): MotionSection
    {
        $section = MotionSection::createEmpty($sectionId, MotionSectionSettings::PUBLIC_YES, $motion->id);
        $section->setData($data);
        $section->save();

        return $section;
    }

    /**
     * @return array<int, string>
     */
    private static function filteredIds(Motion $motion, string $readerLanguage): array
    {
        $filtered = MotionSectionLanguageFilter::filter($motion->getActiveSections(), $readerLanguage);

        return array_map(fn (MotionSection $s) => $s->sectionId, $filtered);
    }

    public function testReaderLanguageWithContentIsShownAlone(): void
    {
        $motionType = self::createMotionType();
        $motion     = self::createMotion($motionType);
        self::saveSection($motion, 2100, 'Titel');
        self::saveSection($motion, 2101, 'Title');
        self::saveSection($motion, 2102, 'Antragstext');
        self::saveSection($motion, 2103, 'Motion text');
        self::saveSection($motion, 2104, 'Grund');

        $de = self::filteredIds($motion, 'de');
        sort($de);
        $this->assertSame([2100, 2102, 2104], $de);

        $en = self::filteredIds($motion, 'en');
        sort($en);
        $this->assertSame([2101, 2103, 2104], $en);
    }

    public function testFallsBackToOtherLanguageWhenOwnIsEmpty(): void
    {
        $motionType = self::createMotionType();
        $motion     = self::createMotion($motionType);
        self::saveSection($motion, 2100, 'Titel');
        self::saveSection($motion, 2101, ''); // English title never filled in
        self::saveSection($motion, 2102, 'Antragstext');
        self::saveSection($motion, 2103, '');
        self::saveSection($motion, 2104, 'Grund');

        // The English reader sees the German fallback for title/body (D4), plus the neutral reason.
        $en = self::filteredIds($motion, 'en');
        sort($en);
        $this->assertSame([2100, 2102, 2104], $en);
    }

    public function testShowsAllLanguagesWithContentWhenReaderLanguageIsNotInTheGroup(): void
    {
        $motionType = self::createMotionType();
        $motion     = self::createMotion($motionType);
        self::saveSection($motion, 2100, 'Titel');
        self::saveSection($motion, 2101, 'Title');
        self::saveSection($motion, 2102, 'Antragstext');
        self::saveSection($motion, 2103, 'Motion text');
        self::saveSection($motion, 2104, 'Grund');

        // A Dutch reader isn't represented in either group at all - both filled languages show up.
        $nl = self::filteredIds($motion, 'nl');
        sort($nl);
        $this->assertSame([2100, 2101, 2102, 2103, 2104], $nl);
    }

    public function testEmptyGroupKeepsTheReadersOwnLanguage(): void
    {
        $motionType = self::createMotionType();
        $motion     = self::createMotion($motionType);
        self::saveSection($motion, 2100, '');
        self::saveSection($motion, 2101, '');
        self::saveSection($motion, 2102, '');
        self::saveSection($motion, 2103, '');
        self::saveSection($motion, 2104, '');

        // Nothing has content anywhere - each reader still gets their own (empty) section back,
        // matching how a single-language site already renders an empty, not-yet-filled-in section.
        $de = self::filteredIds($motion, 'de');
        sort($de);
        $this->assertSame([2100, 2102, 2104], $de);
    }

    public function testAmendmentSectionOnlyCountsAsHavingContentWhenItDiffersFromTheOriginal(): void
    {
        $motionType = self::createMotionType();
        $motion     = self::createMotion($motionType);
        $original   = self::saveSection($motion, 2102, 'Antragstext');

        $unchanged = new AmendmentSection();
        $unchanged->sectionId = 2102;
        $unchanged->public    = MotionSectionSettings::PUBLIC_YES;
        $unchanged->cache     = '';
        $unchanged->setOriginalMotionSection($original);
        $unchanged->setData($original->getData());

        $changed = new AmendmentSection();
        $changed->sectionId = 2102;
        $changed->public    = MotionSectionSettings::PUBLIC_YES;
        $changed->cache     = '';
        $changed->setOriginalMotionSection($original);
        $changed->setData('Geänderter Antragstext');

        // An amendment section pre-filled with (but not differing from) the original motion text
        // must not count as "has content" - otherwise a reader would be shown that untouched
        // section instead of the language the amendment actually changed (see the deviation note
        // in multilanguage-implementation.md for the bug this prevents).
        $this->assertFalse($unchanged->hasContentForFiltering());
        $this->assertTrue($changed->hasContentForFiltering());
    }
}
