<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\models\settings\Consultation;
use app\plugins\dbwv\workflow\Workflow;

class ConsultationSettings extends Consultation
{
    public string $defaultVersionFilter = Workflow::STEP_V1;
}
