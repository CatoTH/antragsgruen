<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\SectionAutofill;
use app\models\motionTypeTemplates\Motion as MotionTemplate;
use app\models\sectionTypes\ISectionType;
use app\models\settings\{AntragsgruenApp, MotionSection as MotionSectionSettings};
use Codeception\Attribute\Group;
use app\models\db\{AmendmentSection, Consultation, ConsultationMotionType, ConsultationSettingsMotionSection, Motion, MotionSection};
use Tests\Support\Helper\DBTestBase;

/**
 * Covers components/SectionAutofill.php end to end, using the test-fixture plugin
 * plugins/test_stub_autofill/Module.php as the only active
 * ModuleBase::fillEmptyMotionSectionContent()/fillEmptyAmendmentSectionContent() implementation -
 * this file is about SectionAutofill's own dispatch logic (empty-check, metadata marking), not about
 * any particular translation backend; plugins/translation_claude has its own dedicated tests. DB-backed
 * for the same reason as MotionSectionLanguageFilterTest - ConsultationSettingsMotionSection/
 * MotionSection are ActiveRecord classes, so even reading a property requires a DB schema lookup.
 */
#[Group('database')]
class SectionAutofillTest extends DBTestBase
{
    /**
     * No plugin is in the test config's active plugin list by default (activating one there would
     * affect every other test creating a motion/amendment). Activating one only for the duration of
     * a single test via reflection keeps the blast radius to this file.
     */
    private static function activatePlugin(?string $pluginId): void
    {
        $ref = new \ReflectionProperty(AntragsgruenApp::class, 'plugins');
        $ref->setAccessible(true);
        $ref->setValue(AntragsgruenApp::getInstance(), ($pluginId === null ? [] : [$pluginId]));
    }

    protected function tearDown(): void
    {
        self::activatePlugin(null);
        parent::tearDown();
    }

    private static function createSectionType(int $id, string $title): ConsultationSettingsMotionSection
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->id            = $id;
        $section->title         = $title;
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->required      = ConsultationSettingsMotionSection::REQUIRED_NO;
        $section->position      = 0;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
        $section->hasAmendments = 1;
        $section->positionRight = 0;

        return $section;
    }

    private static function createMotionType(int $sectionId, string $title): ConsultationMotionType
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);
        $motionType   = MotionTemplate::doCreateMotionType($consultation);
        $motionType->link('motionSections', self::createSectionType($sectionId, $title));

        return $motionType;
    }

    private static function createMotion(ConsultationMotionType $motionType, int $sectionId, string $data): Motion
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

        $section = MotionSection::createEmpty($sectionId, MotionSectionSettings::PUBLIC_YES, $motion->id);
        $section->setData($data);
        $section->save();

        return $motion;
    }

    public function testEmptyMotionSectionIsFilledAndMarked(): void
    {
        self::activatePlugin('test_stub_autofill');

        $motionType = self::createMotionType(2300, 'Text');
        $motion     = self::createMotion($motionType, 2300, '');

        SectionAutofill::fillEmptyMotionSections($motion);

        $motion->refresh();
        $section = $motion->getActiveSections()[0];
        $this->assertSame('dummy', $section->getData());
        $this->assertSame('test_stub_autofill', $section->getAutofillPluginId());
    }

    public function testNonEmptyMotionSectionIsLeftUntouched(): void
    {
        self::activatePlugin('test_stub_autofill');

        $motionType = self::createMotionType(2301, 'Text');
        $motion     = self::createMotion($motionType, 2301, 'Already written by a human');

        SectionAutofill::fillEmptyMotionSections($motion);

        $motion->refresh();
        $section = $motion->getActiveSections()[0];
        $this->assertSame('Already written by a human', $section->getData());
        $this->assertNull($section->getAutofillPluginId());
    }

    public function testNothingIsFilledWithoutAnActivePlugin(): void
    {
        self::activatePlugin(null);

        $motionType = self::createMotionType(2302, 'Text');
        $motion     = self::createMotion($motionType, 2302, '');

        SectionAutofill::fillEmptyMotionSections($motion);

        $motion->refresh();
        $section = $motion->getActiveSections()[0];
        $this->assertSame('', $section->getData());
        $this->assertNull($section->getAutofillPluginId());
    }

    public function testEditingAnAutofilledMotionSectionClearsTheMarker(): void
    {
        self::activatePlugin('test_stub_autofill');

        $motionType = self::createMotionType(2303, 'Text');
        $motion     = self::createMotion($motionType, 2303, '');

        SectionAutofill::fillEmptyMotionSections($motion);
        $motion->refresh();
        $section = $motion->getActiveSections()[0];
        $this->assertSame('test_stub_autofill', $section->getAutofillPluginId());

        $section->setData('A human overwrote the generated text');
        $this->assertNull($section->getAutofillPluginId());
    }

    private static function createAmendment(Motion $motion): \app\models\db\Amendment
    {
        $amendment                        = new \app\models\db\Amendment();
        $amendment->motionId              = $motion->id;
        $amendment->status                = \app\models\db\Amendment::STATUS_DRAFT;
        $amendment->statusString          = '';
        $amendment->titlePrefix           = '';
        $amendment->changeEditorial       = '';
        $amendment->changeText            = '';
        $amendment->changeExplanation     = '';
        $amendment->cache                 = '';
        $amendment->dateCreation          = date('Y-m-d H:i:s');
        $amendment->dateContentModification = date('Y-m-d H:i:s');
        $amendment->save();

        return $amendment;
    }

    public function testUnchangedAmendmentSectionIsFilledAndMarked(): void
    {
        self::activatePlugin('test_stub_autofill');

        $motionType = self::createMotionType(2304, 'Text');
        $motion     = self::createMotion($motionType, 2304, 'Originaltext');
        $amendment  = self::createAmendment($motion);

        $original = $motion->getActiveSections()[0];

        $amendmentSection = new AmendmentSection();
        $amendmentSection->sectionId   = 2304;
        $amendmentSection->amendmentId = $amendment->id;
        $amendmentSection->public      = MotionSectionSettings::PUBLIC_YES;
        $amendmentSection->cache       = '';
        $amendmentSection->setOriginalMotionSection($original);
        // Pre-filled with (and unchanged from) the original, exactly like a real amendment section
        // for a language the submitter didn't touch.
        $amendmentSection->setData($original->getData());
        $amendmentSection->dataRaw = $original->getData();
        $amendmentSection->save();

        $this->assertFalse($amendmentSection->hasContentForFiltering());

        SectionAutofill::fillEmptyAmendmentSections($amendment);

        $amendment->refresh();
        $refilled = $amendment->getActiveSections()[0];
        $this->assertSame('dummy', $refilled->getData());
        $this->assertSame('test_stub_autofill', $refilled->getAutofillPluginId());
    }

    public function testChangedAmendmentSectionIsLeftUntouched(): void
    {
        self::activatePlugin('test_stub_autofill');

        $motionType = self::createMotionType(2305, 'Text');
        $motion     = self::createMotion($motionType, 2305, 'Originaltext');
        $amendment  = self::createAmendment($motion);

        $original = $motion->getActiveSections()[0];

        $amendmentSection = new AmendmentSection();
        $amendmentSection->sectionId   = 2305;
        $amendmentSection->amendmentId = $amendment->id;
        $amendmentSection->public      = MotionSectionSettings::PUBLIC_YES;
        $amendmentSection->cache       = '';
        $amendmentSection->setOriginalMotionSection($original);
        $amendmentSection->setData('Geänderter Text');
        $amendmentSection->dataRaw = 'Geänderter Text';
        $amendmentSection->save();

        SectionAutofill::fillEmptyAmendmentSections($amendment);

        $amendment->refresh();
        $unchanged = $amendment->getActiveSections()[0];
        $this->assertSame('Geänderter Text', $unchanged->getData());
        $this->assertNull($unchanged->getAutofillPluginId());
    }
}
