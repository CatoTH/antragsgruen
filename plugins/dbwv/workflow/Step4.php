<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\components\MotionNumbering;
use app\components\RequestContext;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use app\models\exceptions\Access;
use app\models\forms\MotionDeepCopy;
use app\plugins\dbwv\Module;
use app\models\settings\{PrivilegeQueryContext, Privileges};

class Step4
{
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

        if (Workflow::canMoveToMainV4($motion)) {
            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_4_next', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function moveToMain(Motion $motion, ?int $newTagId): Motion
    {
        if (!Workflow::canMoveToMainV4($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version !== Workflow::STEP_V4) {
            throw new Access('Not allowed to perform this action (in this state)');
        }
        if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V5)) {
            throw new Access('A new version of this motion was already created');
        }

        $targetType = Module::getCorrespondingBundMotionType($motion->getMyMotionType());

        $v5Motion = MotionDeepCopy::copyMotion(
            $motion,
            $targetType,
            null,
            '',
            Workflow::STEP_V5,
            true
        );
        $v5Motion->status = IMotion::STATUS_SUBMITTED_UNSCREENED;
        $v5Motion->proposalStatus = null;
        $v5Motion->proposalReferenceId = null;
        $v5Motion->proposalVisibleFrom = null;
        $v5Motion->proposalComment = null;
        $v5Motion->proposalNotification = null;
        $v5Motion->proposalUserStatus = null;
        $v5Motion->proposalExplanation = null;
        $v5Motion->votingBlockId = null;
        $v5Motion->votingData = null;
        $v5Motion->votingStatus = null;
        $v5Motion->responsibilityId = null;
        $v5Motion->save();

        foreach ($v5Motion->motionSupporters as $motionSupporter) {
            $v5Motion->unlink('motionSupporters', $motionSupporter, true);
        }
        $newProposer = new MotionSupporter();
        $newProposer->motionId = $v5Motion->id;
        $newProposer->position = 0;
        $newProposer->userId = null;
        $newProposer->role = MotionSupporter::ROLE_INITIATOR;
        $newProposer->personType = MotionSupporter::PERSON_ORGANIZATION;
        $newProposer->name = '';
        $newProposer->organization = $motion->getMyConsultation()->title;
        $newProposer->dateCreation = date('Y-m-d H:i:s');
        $newProposer->save();

        if ($newTagId) {
            $newTag = Module::getBundConsultation()->getTagById($newTagId);
            $v5Motion->link('tags', $newTag);
        }

        return $v5Motion;
    }
}
