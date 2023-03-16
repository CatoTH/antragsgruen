<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\components\RequestContext;
use app\models\db\Motion;
use app\models\exceptions\Access;
use app\models\exceptions\NotFound;
use app\models\settings\{PrivilegeQueryContext, Privileges};

class Step2
{
    public static function canSetRecommendation(Motion $motion): bool
    {
        // @TODO Restrict to Working groups
        $ctx = PrivilegeQueryContext::motion($motion);
        return $motion->getMyConsultation()->havePrivilege(Privileges::PRIVILEGE_MOTION_STATUS_EDIT, $ctx);
    }

    public static function renderMotionAdministration(Motion $motion): string
    {
        $html = '';

        if (Step1::canAssignTopic($motion)) {
            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_2_edit', ['motion' => $motion]
            );
        }

        if (self::canSetRecommendation($motion)) {
            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_2_next', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function gotoNext(Motion $motion, array $postparams): void
    {
        if (!self::canSetRecommendation($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version !== Workflow::STEP_V2) {
            throw new Access('Not allowed to perform this action (in this state)');
        }

        $motion->version = Workflow::STEP_V3;
        $motion->save();
    }

    public static function edit(Motion $motion, array $postparams): void
    {
        if (!Step1::canAssignTopic($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version !== Workflow::STEP_V2) {
            throw new Access('Not allowed to perform this action (in this state)');
        }

        $agendaItem = $motion->getMyConsultation()->getAgendaItem(intval($postparams['agendaItem']));
        if (!$agendaItem) {
            throw new NotFound('Agenda item not found');
        }

        $motion->agendaItemId = $agendaItem->id;
        $motion->titlePrefix = $postparams['motionPrefix'];
        $motion->save();
    }
}
