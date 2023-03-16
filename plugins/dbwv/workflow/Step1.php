<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\components\RequestContext;
use app\models\db\Motion;
use app\models\settings\{PrivilegeQueryContext, Privileges};

class Step1
{
    public static function canAssignTopic(Motion $motion): bool
    {
        // @TODO Restrict to LV Recht
        $ctx = PrivilegeQueryContext::motion($motion);
        return $motion->getMyConsultation()->havePrivilege(Privileges::PRIVILEGE_MOTION_STATUS_EDIT, $ctx);
    }

    public static function renderMotionAdministration(Motion $motion): string
    {
        if (!self::canAssignTopic($motion)) {
            return '';
        }

        return RequestContext::getController()->renderPartial(
            '@app/plugins/dbwv/views/admin_step_1', ['motion' => $motion]
        );
    }
}
