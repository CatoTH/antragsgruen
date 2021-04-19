<?php

declare(strict_types=1);

namespace app\plugins\bgs;

use app\models\settings\IMotionStatus;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    /**
     * @return IMotionStatus[]
     */
    public static function getAdditionalIMotionStatuses(): array
    {
        return [
            new IMotionStatus(120, 'Antrag mit Klärungsbedarf', null, false, true),
        ];
    }
}
