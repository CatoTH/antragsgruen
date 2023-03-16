<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\models\db\Motion;
use app\models\layoutHooks\Hooks;
use app\plugins\dbwv\workflow\{Step1, Workflow};

class LayoutHooks extends Hooks
{
    public function beforeMotionView(string $before, Motion $motion): string
    {
        switch (intval($motion->version)) {
            case Workflow::STEP_V1:
                return Step1::renderMotionAdministration($motion) . $before;
            default:
                return $before;
        }
    }
}
