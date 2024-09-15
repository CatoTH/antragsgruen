<?php

namespace Tests\Unit;

use app\models\motionTypeTemplates\Motion;
use app\models\sectionTypes\ISectionType;
use Codeception\Attribute\Group;
use app\models\db\{Consultation, ConsultationMotionType, ConsultationSettingsMotionSection};
use app\models\forms\MotionDeepCopy;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class MotionSectionMappingTest extends DBTestBase
{
    public function testMappingToItselfWorks(): void
    {
        $fromType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Title', true, true),
            self::createDummySection(1001, ISectionType::TYPE_TEXT_SIMPLE, 'Text', true, true),
            self::createDummySection(1002, ISectionType::TYPE_TEXT_SIMPLE, 'Reason', false, false)
        ]);

        $mapping = MotionDeepCopy::getMotionSectionMapping($fromType, $fromType, []);
        $this->assertSame([
            1000 => 1000,
            1001 => 1001,
            1002 => 1002,
        ], $mapping);
    }

    public function testMappingToCompatibleTypeWorks(): void
    {
        $fromType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Title', true, true),
            self::createDummySection(1001, ISectionType::TYPE_TEXT_SIMPLE, 'Text', true, true),
            self::createDummySection(1002, ISectionType::TYPE_TEXT_SIMPLE, 'Reason', false, false)
        ]);

        $toType = self::createDummyMotionType([
            self::createDummySection(1003, ISectionType::TYPE_TITLE, 'Title', true, true),
            self::createDummySection(1004, ISectionType::TYPE_TEXT_SIMPLE, 'Text', true, true),
            self::createDummySection(1005, ISectionType::TYPE_TEXT_SIMPLE, 'Reason', false, false)
        ]);

        $mapping = MotionDeepCopy::getMotionSectionMapping($fromType, $toType, []);
        $this->assertSame([
            1000 => 1003,
            1001 => 1004,
            1002 => 1005,
        ], $mapping);
    }

    public function testMappingToIncompatibleTypeDoesNotWork1(): void
    {
        $fromType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Title', true, true),
            self::createDummySection(1001, ISectionType::TYPE_TEXT_SIMPLE, 'Text', true, true),
            self::createDummySection(1002, ISectionType::TYPE_IMAGE, 'Image', false, false)
        ]);

        $toType = self::createDummyMotionType([
            self::createDummySection(1003, ISectionType::TYPE_TITLE, 'Title', true, true),
            self::createDummySection(1004, ISectionType::TYPE_TEXT_SIMPLE, 'Text', true, true),
            self::createDummySection(1005, ISectionType::TYPE_TEXT_SIMPLE, 'Reason', false, false)
        ]);

        $mapping = MotionDeepCopy::getMotionSectionMapping($fromType, $toType, []);
        $this->assertSame(null, $mapping);
    }

    public function testMappingToIncompatibleTypeDoesNotWork2(): void
    {
        $toType = self::createDummyMotionType([
            self::createDummySection(1005, ISectionType::TYPE_TITLE, 'Title', true, true),
            self::createDummySection(1006, ISectionType::TYPE_TEXT_SIMPLE, 'Text', true, true),
            self::createDummySection(1007, ISectionType::TYPE_TEXT_SIMPLE, 'Text', true, true),
            self::createDummySection(1008, ISectionType::TYPE_TEXT_SIMPLE, 'Reason', false, false),
            self::createDummySection(1009, ISectionType::TYPE_IMAGE, 'Image', false, false),
        ]);

        $fromType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Title', true, true),
            self::createDummySection(1001, ISectionType::TYPE_IMAGE, 'Image', false, false),
            self::createDummySection(1002, ISectionType::TYPE_TABULAR, 'Tabular data', false, false),
            self::createDummySection(1003, ISectionType::TYPE_TEXT_SIMPLE, 'Introduction', true, true),
            self::createDummySection(1004, ISectionType::TYPE_IMAGE, 'Signature', false, false),
        ]);

        $mapping = MotionDeepCopy::getMotionSectionMapping($fromType, $toType, []);
        $this->assertSame(null, $mapping);
    }

    public function testMappingOnlyAmendableWorks(): void
    {
        $fromType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Title', true, true),
            self::createDummySection(1001, ISectionType::TYPE_TEXT_SIMPLE, 'Text', true, true),
            self::createDummySection(1002, ISectionType::TYPE_IMAGE, 'Image', false, false)
        ]);

        $toType = self::createDummyMotionType([
            self::createDummySection(1003, ISectionType::TYPE_TITLE, 'Title', true, true),
            self::createDummySection(1004, ISectionType::TYPE_TEXT_SIMPLE, 'Text', true, true),
            self::createDummySection(1005, ISectionType::TYPE_TEXT_SIMPLE, 'Reason', false, false)
        ]);

        $mapping = MotionDeepCopy::getMotionSectionMapping($fromType, $toType, [MotionDeepCopy::SKIP_NON_AMENDABLE]);
        $this->assertSame([
            1000 => 1003,
            1001 => 1004,
        ], $mapping);
    }

    public function testMappingMotionToProgressWorks(): void
    {
        $fromType = self::createDummyMotionType([
            self::createDummySection(1000, ISectionType::TYPE_TITLE, 'Title', true, true),
            self::createDummySection(1001, ISectionType::TYPE_TEXT_SIMPLE, 'Text', true, true),
            self::createDummySection(1002, ISectionType::TYPE_TEXT_SIMPLE, 'Image', false, false)
        ]);

        $toType = self::createDummyMotionType([
            self::createDummySection(1003, ISectionType::TYPE_TITLE, 'Title', true, true),
            self::createDummySection(1004, ISectionType::TYPE_TEXT_SIMPLE, 'Resolution', true, true),
            self::createDummySection(1005, ISectionType::TYPE_TEXT_EDITORIAL, 'Progress', false, false)
        ]);

        $mapping = MotionDeepCopy::getMotionSectionMapping($fromType, $toType, [MotionDeepCopy::SKIP_NON_AMENDABLE]);
        $this->assertSame([
            1000 => 1003,
            1001 => 1004,
        ], $mapping);
    }

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

    private static function createDummySection(int $id, int $type, string $title, bool $amendable, bool $required): ConsultationSettingsMotionSection
    {
        $section = new ConsultationSettingsMotionSection();
        $section->id = $id;
        $section->title = $title;
        $section->type = $type;
        $section->hasAmendments = ($amendable ? 1 : 0);
        $section->required = ($required ? ConsultationSettingsMotionSection::REQUIRED_YES : ConsultationSettingsMotionSection::REQUIRED_NO);

        $section->position      = 0;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
        $section->positionRight = 0;
        $section->settings      = null;

        $section->save();

        return $section;
    }
}
