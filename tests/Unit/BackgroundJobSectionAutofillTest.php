<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\BackgroundJobScheduler;
use app\models\backgroundJobs\{FillEmptyAmendmentSections, FillEmptyMotionSections, SendNotification};
use app\models\motionTypeTemplates\Motion as MotionTemplate;
use app\models\sectionTypes\ISectionType;
use app\models\settings\{AntragsgruenApp, MotionSection as MotionSectionSettings};
use Codeception\Attribute\Group;
use app\models\db\{Amendment, Consultation, ConsultationMotionType, ConsultationSettingsMotionSection, Motion, MotionSection};
use Tests\Support\Helper\DBTestBase;

/**
 * Covers the section-autofill mechanism (§19) being run through the background-job system
 * (models/backgroundJobs/FillEmptyMotionSections.php, FillEmptyAmendmentSections.php,
 * components/BackgroundJobScheduler.php) instead of calling components/SectionAutofill.php directly -
 * synchronous when config.json's backgroundJobs.sectionAutofill is unset/false (the default, and the
 * only case exercised by MotionEditForm/AmendmentEditForm's own tests, which never enable it), queued
 * into the `backgroundJob` table otherwise, exactly like SendNotification already does for e-mails.
 * DB-backed for the same reason as MotionSectionLanguageFilterTest.
 */
#[Group('database')]
class BackgroundJobSectionAutofillTest extends DBTestBase
{
    private static function activatePlugin(?string $pluginId): void
    {
        $ref = new \ReflectionProperty(AntragsgruenApp::class, 'plugins');
        $ref->setAccessible(true);
        $ref->setValue(AntragsgruenApp::getInstance(), $pluginId === null ? [] : [$pluginId]);
    }

    protected function tearDown(): void
    {
        self::activatePlugin(null);
        AntragsgruenApp::getInstance()->backgroundJobs = null;
        parent::tearDown();
    }

    private static function createSectionType(int $id): ConsultationSettingsMotionSection
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->id            = $id;
        $section->title         = 'Text';
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

    private static function createMotionType(int $sectionId): ConsultationMotionType
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);
        $motionType   = MotionTemplate::doCreateMotionType($consultation);
        $motionType->link('motionSections', self::createSectionType($sectionId));

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

    private static function createAmendment(Motion $motion): Amendment
    {
        $amendment                          = new Amendment();
        $amendment->motionId                = $motion->id;
        $amendment->status                  = Amendment::STATUS_DRAFT;
        $amendment->statusString            = '';
        $amendment->titlePrefix             = '';
        $amendment->changeEditorial         = '';
        $amendment->changeText              = '';
        $amendment->changeExplanation       = '';
        $amendment->cache                   = '';
        $amendment->dateCreation            = date('Y-m-d H:i:s');
        $amendment->dateContentModification = date('Y-m-d H:i:s');
        $amendment->save();

        return $amendment;
    }

    public function testFillEmptyMotionSectionsJobFillsTheMotion(): void
    {
        self::activatePlugin('test_stub_autofill');

        $motionType = self::createMotionType(2500);
        $motion     = self::createMotion($motionType, 2500, '');

        (new FillEmptyMotionSections($motion->getMyConsultation(), $motion->id))->execute();

        $motion->refresh();
        $this->assertSame('dummy', $motion->getActiveSections()[0]->getData());
    }

    public function testFillEmptyMotionSectionsJobNoOpsForAMissingMotion(): void
    {
        self::activatePlugin('test_stub_autofill');

        (new FillEmptyMotionSections(null, 999999))->execute();

        $this->assertTrue(true); // Reaching this line without an exception is the actual assertion.
    }

    public function testFillEmptyAmendmentSectionsJobFillsTheAmendment(): void
    {
        self::activatePlugin('test_stub_autofill');

        $motionType = self::createMotionType(2501);
        $motion     = self::createMotion($motionType, 2501, 'Originaltext');
        $amendment  = self::createAmendment($motion);

        $original         = $motion->getActiveSections()[0];
        $amendmentSection = new \app\models\db\AmendmentSection();
        $amendmentSection->sectionId   = 2501;
        $amendmentSection->amendmentId = $amendment->id;
        $amendmentSection->public      = MotionSectionSettings::PUBLIC_YES;
        $amendmentSection->cache       = '';
        $amendmentSection->setOriginalMotionSection($original);
        $amendmentSection->setData($original->getData());
        $amendmentSection->dataRaw = $original->getData();
        $amendmentSection->save();

        (new FillEmptyAmendmentSections($amendment->getMyConsultation(), $amendment->id))->execute();

        $amendment->refresh();
        $this->assertSame('dummy', $amendment->getActiveSections()[0]->getData());
    }

    public function testFillEmptyAmendmentSectionsJobNoOpsForAMissingAmendment(): void
    {
        self::activatePlugin('test_stub_autofill');

        (new FillEmptyAmendmentSections(null, 999999))->execute();

        $this->assertTrue(true);
    }

    public function testJobsDeclareDistinctConfigFlags(): void
    {
        $motionJob       = new FillEmptyMotionSections(null, 1);
        $amendmentJob    = new FillEmptyAmendmentSections(null, 1);
        $notificationJob = new SendNotification(null, 1, 'a@example.org', null, 'subj', 'text', 'html', null, 'from@example.org', 'From', null);

        $this->assertSame('sectionAutofill', $motionJob->getConfigFlagName());
        $this->assertSame('sectionAutofill', $amendmentJob->getConfigFlagName());
        $this->assertSame('notifications', $notificationJob->getConfigFlagName());
    }

    public function testExecuteOrScheduleJobRunsSynchronouslyByDefault(): void
    {
        self::activatePlugin('test_stub_autofill');
        AntragsgruenApp::getInstance()->backgroundJobs = null;

        $motionType = self::createMotionType(2502);
        $motion     = self::createMotion($motionType, 2502, '');

        BackgroundJobScheduler::executeOrScheduleJob(new FillEmptyMotionSections($motion->getMyConsultation(), $motion->id));

        $motion->refresh();
        $this->assertSame('dummy', $motion->getActiveSections()[0]->getData());
        $this->assertSame('0', (string) \Yii::$app->db->createCommand('SELECT COUNT(*) FROM backgroundJob')->queryScalar());
    }

    public function testExecuteOrScheduleJobQueuesWhenEnabled(): void
    {
        self::activatePlugin('test_stub_autofill');
        AntragsgruenApp::getInstance()->backgroundJobs = ['sectionAutofill' => true];

        $motionType = self::createMotionType(2503);
        $motion     = self::createMotion($motionType, 2503, '');

        BackgroundJobScheduler::executeOrScheduleJob(new FillEmptyMotionSections($motion->getMyConsultation(), $motion->id));

        // Not filled yet - only queued; a background worker (commands/BackgroundJobController.php)
        // would process it.
        $motion->refresh();
        $this->assertSame('', $motion->getActiveSections()[0]->getData());

        $row = \Yii::$app->db->createCommand('SELECT * FROM backgroundJob')->queryOne();
        $this->assertNotNull($row);
        $this->assertSame(FillEmptyMotionSections::TYPE_ID, $row['type']);
        $payload = json_decode((string) $row['payload'], true);
        // The serializer (components/Tools::getSerializer()) converts property names to snake_case.
        $this->assertSame($motion->id, $payload['motion_id']);

        // The queued row must also be usable end to end the way BackgroundJobProcessor actually
        // consumes it (deserialize, then execute()) - not just structurally present.
        $reconstructedJob = \app\models\backgroundJobs\IBackgroundJob::fromJson(
            (int) $row['id'],
            $row['type'],
            $row['siteId'] > 0 ? (int) $row['siteId'] : null,
            $row['consultationId'] > 0 ? (int) $row['consultationId'] : null,
            $row['payload']
        );
        $reconstructedJob->execute();

        $motion->refresh();
        $this->assertSame('dummy', $motion->getActiveSections()[0]->getData());
    }

    public function testExecuteOrScheduleJobStaysSynchronousWhenOnlyNotificationsIsEnabled(): void
    {
        self::activatePlugin('test_stub_autofill');
        AntragsgruenApp::getInstance()->backgroundJobs = ['notifications' => true];

        $motionType = self::createMotionType(2504);
        $motion     = self::createMotion($motionType, 2504, '');

        BackgroundJobScheduler::executeOrScheduleJob(new FillEmptyMotionSections($motion->getMyConsultation(), $motion->id));

        // sectionAutofill specifically isn't enabled here, so this must still run inline - proving the
        // two flags are independent, not aliases of each other.
        $motion->refresh();
        $this->assertSame('dummy', $motion->getActiveSections()[0]->getData());
        $this->assertSame('0', (string) \Yii::$app->db->createCommand('SELECT COUNT(*) FROM backgroundJob')->queryScalar());
    }
}
