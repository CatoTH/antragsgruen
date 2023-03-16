<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\components\RequestContext;
use app\models\db\Motion;
use app\models\exceptions\Access;
use app\models\exceptions\NotFound;
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
            '@app/plugins/dbwv/views/admin_step_1_next', ['motion' => $motion]
        );
    }

    public static function gotoNext(Motion $motion, array $postparams): void
    {
        if (!self::canAssignTopic($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version !== Workflow::STEP_V1) {
            throw new Access('Not allowed to perform this action (in this state)');
        }

        $agendaItem = $motion->getMyConsultation()->getAgendaItem(intval($postparams['agendaItem']));
        if (!$agendaItem) {
            throw new NotFound('Agenda item not found');
        }

        $motion->version = Workflow::STEP_V2;
        $motion->agendaItemId = $agendaItem->id;
        $motion->titlePrefix = $postparams['motionPrefix'];
        $motion->save();

        if (isset($postparams['withChanges'])) {
            die("@TODO");
        }
    }
}
