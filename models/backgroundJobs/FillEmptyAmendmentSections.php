<?php

declare(strict_types=1);

namespace app\models\backgroundJobs;

use app\components\SectionAutofill;
use app\models\db\{Amendment, Consultation};

/**
 * The amendment equivalent of FillEmptyMotionSections - see that class for the reasoning (only
 * $amendmentId is carried in the job payload; execute() always re-fetches fresh data).
 */
class FillEmptyAmendmentSections extends IBackgroundJob
{
    public const TYPE_ID = 'FILL_EMPTY_AMENDMENT_SECTIONS';

    public function __construct(
        ?Consultation $consultation,
        public int $amendmentId,
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
        $amendment = Amendment::findOne($this->amendmentId);
        if ($amendment) {
            SectionAutofill::fillEmptyAmendmentSections($amendment);
        }
    }
}
