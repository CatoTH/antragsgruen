<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\components\MotionNumbering;
use app\components\RequestContext;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\exceptions\Access;
use app\models\forms\MotionDeepCopy;
use app\models\settings\{PrivilegeQueryContext, Privileges};

class Step3
{

    public static function renderMotionAdministration(Motion $motion): string
    {
        $html = '';

        if (Workflow::canSetResolutionV3($motion)) {
            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_3_next', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function gotoNext(Motion $motion): Motion
    {
        if (!Workflow::canSetResolutionV3($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version === Workflow::STEP_V3) {
            if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V4)) {
                throw new Access('A new version of this motion was already created');
            }
            $v4Motion = MotionDeepCopy::copyMotion(
                $motion,
                $motion->getMyMotionType(),
                $motion->agendaItem,
                $motion->titlePrefix,
                Workflow::STEP_V4,
                true
            );
            $v4Motion->status = IMotion::STATUS_ACCEPTED;
            $v4Motion->save();
        } else {
            if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V4)) {
                throw new Access('A new version of this motion was already created');
            }
            $v4Motion = $motion;
        }
        unset($motion);

        return $v4Motion;
    }
}
