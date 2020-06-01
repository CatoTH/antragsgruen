<?php

namespace app\plugins\egp;

use app\models\supportTypes\SupportBase;
use app\models\db\IMotion;

class Permissions extends \app\models\siteSpecificBehavior\Permissions
{
    public function canFinishSupportCollection(IMotion $motion, SupportBase $supportType): bool
    {
        return false;
    }
}
