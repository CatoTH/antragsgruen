<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\components\RequestContext;
use app\models\db\{ConsultationSettingsTag, IMotion, Motion};
use app\models\exceptions\{Access, NotFound};
use app\models\settings\{PrivilegeQueryContext, Privileges};

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

        if (Workflow::canSetRecommendationV2($motion)) {
            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_2_next', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function gotoNext(Motion $motion, array $postparams): void
    {
        if (!Workflow::canSetRecommendationV2($motion)) {
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
        if (!Workflow::canMakeEditorialChangesV1($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version !== Workflow::STEP_V2) {
            throw new Access('Not allowed to perform this action (in this state)');
        }

        $tag = $motion->getMyConsultation()->getTagById(intval($postparams['tag']));
        if (!$tag || $tag->type !== ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) {
            throw new NotFound('Tag not found');
        }

        $motion->titlePrefix = $postparams['motionPrefix'];
        $motion->status = IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED;
        $motion->save();

        $motion->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, [$tag->id]);
    }
}
