<?php

declare(strict_types=1);

namespace app\models\backgroundJobs;

use app\components\SectionAutofill;
use app\models\db\{Consultation, Motion};

/**
 * Runs components\SectionAutofill::fillEmptyMotionSections() for one motion - see
 * BackgroundJobScheduler::executeOrScheduleJob(), which runs this inline if background job
 * processing is disabled (the default) or queues it (config.json's backgroundJobs.sectionAutofill)
 * otherwise, exactly like SendNotification already does for e-mails.
 *
 * Only $motionId is carried in the job payload, not the Motion itself - a queued job is
 * (de)serialized to/from JSON and may run in an entirely separate process, so execute() always
 * re-fetches fresh data rather than relying on whatever the caller had in memory.
 */
class FillEmptyMotionSections extends IBackgroundJob
{
    public const TYPE_ID = 'FILL_EMPTY_MOTION_SECTIONS';

    public function __construct(
        ?Consultation $consultation,
        public int $motionId,
    ) {
        $this->consultation = $consultation;
        $this->site = $consultation?->site;
    }

    public function getTypeId(): string
    {
        return self::TYPE_ID;
    }

    public function getConfigFlagName(): string
    {
        return 'sectionAutofill';
    }

    public function execute(): void
    {
        $motion = Motion::findOne($this->motionId);
        if ($motion) {
            SectionAutofill::fillEmptyMotionSections($motion);
        }
    }
}
