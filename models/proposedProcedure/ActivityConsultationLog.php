<?php

declare(strict_types=1);

namespace app\models\proposedProcedure;

use app\models\db\ConsultationLog;

class ActivityConsultationLog extends IActivity
{
    private ConsultationLog $consultationLog;

    public function __construct(ConsultationLog $consultationLog) {
        $this->consultationLog = $consultationLog;
        $this->date = new \DateTimeImmutable($consultationLog->actionTime);
    }

    public function getConsultationLog(): ConsultationLog
    {
        return $this->consultationLog;
    }
}
