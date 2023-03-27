<?php

namespace app\plugins\egp;

use app\models\db\IMotion;
use app\models\supportTypes\SupportBase;

class Permissions extends \app\models\settings\Permissions
{
    public function canFinishSupportCollection(IMotion $motion, SupportBase $supportType): bool
    {
        return false;
    }
}
