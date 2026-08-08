<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\motionTypeTemplates\Motion as MotionTemplate;
use app\models\sectionTypes\ISectionType;
use Codeception\Attribute\Group;
use app\models\db\{Consultation, ConsultationMotionType, ConsultationSettingsMotionSection, Motion, MotionSection};
use Tests\Support\Helper\DBTestBase;

/**
 * Covers D3 (canonical vs. per-language title resolution): IMotion::getTitleSection(),
 * Motion::getTitleForDisplay() and Motion::getTitleSectionForDisplay(). DB-backed for the same
 * reason as MotionSectionLanguageFilterTest - see multilanguage-implementation.md.
 */
#[Group('database')]
class MotionTitleForDisplayTest extends DBTestBase
{
    private static function createSectionType(int $id, ?string $language, ?string $languageGrouping = null): ConsultationSettingsMotionSection
    {
        $section            = new ConsultationSettingsMotionSection();
        $section->id        = $id;
        $section->title     = 'Title';
        $section->type      = ISectionType::TYPE_TITLE;
        $section->status    = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->required  = ConsultationSettingsMotionSection::REQUIRED_NO;
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

    /** Motion type with two title sections: German (primary, per wordingBase "de") and English. */
    private static function createMotionType(): ConsultationMotionType
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);
        $motionType   = MotionTemplate::doCreateMotionType($consultation);

        $motionType->link('motionSections', self::createSectionType(2200, 'de', 'title'));
        $motionType->link('motionSections', self::createSectionType(2201, 'en', 'title'));

        return $motionType;
    }

    private static function createMotion(ConsultationMotionType $motionType, string $deTitle, string $enTitle): Motion
    {
        $motion                         = new Motion();
        $motion->motionTypeId            = $motionType->id;
        $motion->consultationId          = $motionType->consultationId;
        $motion->title                   = '';
        $motion->titlePrefix             = '';
        $motion->version                 = Motion::VERSION_DEFAULT;
        $motion->status                  = Motion::STATUS_DRAFT;
        $motion->dateCreation             = date('Y-m-d H:i:s');
        $motion->dateContentModification  = date('Y-m-d H:i:s');
        $motion->cache                    = '';
        $motion->save();

        $de = MotionSection::createEmpty(2200, \app\models\settings\MotionSection::PUBLIC_YES, $motion->id);
        $de->setData($deTitle);
        $de->save();

        $en = MotionSection::createEmpty(2201, \app\models\settings\MotionSection::PUBLIC_YES, $motion->id);
        $en->setData($enTitle);
        $en->save();

        $motion->refreshTitle();

        return $motion;
    }

    public function testCanonicalTitleIsThePrimaryLanguage(): void
    {
        // Consultation 1's wordingBase makes German the primary language (see MotionSectionMappingTest
        // for the same fixture assumption).
        $motionType = self::createMotionType();
        $motion     = self::createMotion($motionType, 'Klimaschutz jetzt', 'Climate protection now');

        $this->assertSame('Klimaschutz jetzt', $motion->title);
        $this->assertSame('Klimaschutz jetzt', $motion->getTitleSection()->getData());
    }

    public function testGetTitleSectionPrefersAnExactLanguageMatch(): void
    {
        $motionType = self::createMotionType();
        $motion     = self::createMotion($motionType, 'Klimaschutz jetzt', 'Climate protection now');

        $this->assertSame('Klimaschutz jetzt', $motion->getTitleSection('de')->getData());
        $this->assertSame('Climate protection now', $motion->getTitleSection('en')->getData());
    }

    public function testTitleForDisplayUsesTheRequestedLanguageWhenItHasContent(): void
    {
        $motionType = self::createMotionType();
        $motion     = self::createMotion($motionType, 'Klimaschutz jetzt', 'Climate protection now');

        $this->assertSame('Climate protection now', $motion->getTitleForDisplay('en'));
        $this->assertSame('Klimaschutz jetzt', $motion->getTitleForDisplay('de'));
    }

    public function testTitleForDisplayFallsBackToCanonicalWhenTheRequestedLanguageIsEmpty(): void
    {
        $motionType = self::createMotionType();
        // English title never filled in.
        $motion     = self::createMotion($motionType, 'Klimaschutz jetzt', '');

        $this->assertSame('Klimaschutz jetzt', $motion->getTitleForDisplay('en'));

        // The section actually used for display must be the German (canonical) one, so a view
        // asking needsLanguageLabel() on it gets a consistent answer with what's actually shown.
        $usedSection = $motion->getTitleSectionForDisplay('en');
        $this->assertSame('de', $usedSection->getDisplayLanguage());
        $this->assertTrue($usedSection->needsLanguageLabel('en'));
    }

    public function testTitleSectionForDisplayReturnsTheMatchWhenItHasContent(): void
    {
        $motionType = self::createMotionType();
        $motion     = self::createMotion($motionType, 'Klimaschutz jetzt', 'Climate protection now');

        $usedSection = $motion->getTitleSectionForDisplay('en');
        $this->assertSame('en', $usedSection->getDisplayLanguage());
        $this->assertFalse($usedSection->needsLanguageLabel('en'));
    }
}
