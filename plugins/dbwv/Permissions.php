<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\models\db\Motion;
use app\plugins\dbwv\workflow\Workflow;

class Permissions extends \app\models\settings\Permissions
{
    public function motionCanEditText(Motion $motion): bool
    {
        if (in_array($motion->version, [Workflow::STEP_V1, Workflow::STEP_V2]) && Workflow::canMakeEditorialChangesV1($motion)) {
            return true;
        }
        if ($motion->version === Workflow::STEP_V5 && Workflow::canMakeEditorialChangesV5($motion)) {
            return true;
        }

        return parent::motionCanEditText($motion);
    }

    public function motionCanEditInitiators(Motion $motion): bool
    {
        return parent::motionCanEditText($motion);
    }
}
