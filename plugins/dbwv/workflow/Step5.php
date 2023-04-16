<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\models\db\ConsultationSettingsTag;
use app\models\db\IMotion;
use app\models\exceptions\NotFound;
use app\components\{MotionNumbering, RequestContext};
use app\models\forms\MotionDeepCopy;
use app\models\db\Motion;
use app\models\exceptions\Access;

class Step5
{
    public static function renderMotionAdministration(Motion $motion): string
    {
        $html = '';

        if (Workflow::canMakeEditorialChangesV5($motion)) {
            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_5_assign_number', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function saveNumber(Motion $motion, array $postparams): Motion
    {
        if (!Workflow::canMakeEditorialChangesV5($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version !== Workflow::STEP_V5) {
            throw new Access('Not allowed to perform this action (in this state)');
        }

        if (count($motion->getPublicTopicTags()) > 0) {
            $tag = $motion->getPublicTopicTags()[0];
            $subtag = $motion->getMyConsultation()->getTagById(intval($postparams['subtag']));
            if (!$subtag || $subtag->type !== ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE || $subtag->parentTagId !== $tag->id) {
                throw new NotFound('Tag not found');
            }
            $motion->setTags(ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE, [$subtag->id]);
        }

        $motion->titlePrefix = $postparams['motionPrefix'];
        $motion->status = IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED;
        $motion->save();

        return $motion;
    }
}
