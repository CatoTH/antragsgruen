<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\components\RequestContext;
use app\models\db\Motion;
use app\models\exceptions\Access;
use app\models\settings\{PrivilegeQueryContext, Privileges};

class Step4
{
    public static function canMoveToMain(Motion $motion): bool
    {
        // @TODO Restrict to admins
        $ctx = PrivilegeQueryContext::motion($motion);
        return $motion->getMyConsultation()->havePrivilege(Privileges::PRIVILEGE_MOTION_STATUS_EDIT, $ctx);
    }

    public static function renderMotionAdministration(Motion $motion): string
    {
        $html = '';

        /*
        if (Step3::canSetResolution($motion)) {
            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_3_edit', ['motion' => $motion]
            );
        }
        */

        if (self::canMoveToMain($motion)) {
            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_4_next', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function gotoNext(Motion $motion, array $postparams): void
    {
        if (!self::canMoveToMain($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version !== Workflow::STEP_V4) {
            throw new Access('Not allowed to perform this action (in this state)');
        }

        $motion->version = Workflow::STEP_V5;
        $motion->save();
    }
}