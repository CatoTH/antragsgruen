<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\components\RequestContext;
use app\plugins\dbwv\Module;
use app\models\db\{ConsultationSettingsTag, IMotion, Motion};
use app\models\exceptions\{Access, NotFound};
use app\models\settings\{PrivilegeQueryContext, Privileges};

class Step1
{
    public static function canAssignTopic(Motion $motion): bool
    {
        $ctx = PrivilegeQueryContext::motion($motion);
        return $motion->getMyConsultation()->havePrivilege(Module::PRIVILEGE_DBWV_V1_ASSIGN_TOPIC, $ctx);
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

        $tag = $motion->getMyConsultation()->getTagById(intval($postparams['tag']));
        if (!$tag || $tag->type !== ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) {
            throw new NotFound('Tag not found');
        }

        $motion->version = Workflow::STEP_V2;
        $motion->titlePrefix = $postparams['motionPrefix'];
        $motion->status = IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED;
        $motion->save();

        $motion->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, [$tag->id]);

        if (isset($postparams['withChanges'])) {
            die("@TODO");
        }
    }
}
