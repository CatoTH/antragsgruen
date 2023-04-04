<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\components\{MotionNumbering, RequestContext};
use app\models\forms\MotionDeepCopy;
use app\models\db\Motion;
use app\models\exceptions\Access;

class Step2
{
    public static function renderMotionAdministration(Motion $motion): string
    {
        $html = '';

        if (Workflow::canMakeEditorialChangesV1($motion)) {
            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_1_assign_number', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function gotoNext(Motion $motion): Motion
    {
        if (!Workflow::canSetRecommendationV2($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if (!in_array($motion->version, [Workflow::STEP_V2, Workflow::STEP_V3, true])) {
            throw new Access('Not allowed to perform this action (in this state)');
        }

        if ($motion->version === Workflow::STEP_V2) {
            if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V3)) {
                throw new Access('A new version of this motion was already created');
            }
            $v3Motion = MotionDeepCopy::copyMotion(
                $motion,
                $motion->getMyMotionType(),
                $motion->agendaItem,
                $motion->titlePrefix,
                Workflow::STEP_V3,
                true
            );
        } else {
            if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V4)) {
                throw new Access('A new version of this motion was already created');
            }
            $v3Motion = $motion;
        }
        unset($motion);

        return $v3Motion;
    }
}
