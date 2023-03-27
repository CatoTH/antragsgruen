<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\components\RequestContext;
use app\models\forms\MotionDeepCopy;
use app\plugins\dbwv\Module;
use app\models\db\{ConsultationSettingsTag, IMotion, Motion};
use app\models\exceptions\{Access, NotFound};
use app\models\settings\{PrivilegeQueryContext, Privileges};

class Step1
{
    public static function renderMotionAdministration(Motion $motion): string
    {
        if (!Workflow::canMakeEditorialChangesV1($motion)) {
            return '';
        }

        return RequestContext::getController()->renderPartial(
            '@app/plugins/dbwv/views/admin_step_1_assign_number', ['motion' => $motion]
        );
    }

    public static function saveEditorial(Motion $motion, array $postparams): Motion
    {
        if (!Workflow::canMakeEditorialChangesV1($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if (!in_array($motion->version, [Workflow::STEP_V1, Workflow::STEP_V2, true])) {
            throw new Access('Not allowed to perform this action (in this state)');
        }

        if ($motion->version === Workflow::STEP_V1) {
            $v2Motion = MotionDeepCopy::copyMotion(
                $motion,
                $motion->getMyMotionType(),
                $motion->agendaItem,
                $postparams['motionPrefix'],
                Workflow::STEP_V2,
                true
            );
        } else {
            $v2Motion = $motion;
        }
        unset($motion);

        if (count($v2Motion->getPublicTopicTags()) > 0) {
            $tag = $v2Motion->getPublicTopicTags()[0];
            $subtag = $v2Motion->getMyConsultation()->getTagById(intval($postparams['subtag']));
            if (!$subtag || $subtag->type !== ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE || $subtag->parentTagId !== $tag->id) {
                throw new NotFound('Tag not found');
            }
            $v2Motion->setTags(ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE, [$subtag->id]);
        }

        $v2Motion->titlePrefix = $postparams['motionPrefix'];
        $v2Motion->status = IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED;
        $v2Motion->save();

        return $v2Motion;
    }
}
