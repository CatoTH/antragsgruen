<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\components\MotionNumbering;
use app\models\db\{Consultation, ConsultationMotionType, IMotion, Motion, User};
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\plugins\dbwv\workflow\Workflow;

class Permissions extends \app\models\settings\Permissions
{
    public function motionCanEditText(Motion $motion): bool
    {
        $consultation = $motion->getMyConsultation();
        $motionType = $motion->getMyMotionType();

        if (in_array($motion->version, [Workflow::STEP_V1, Workflow::STEP_V2]) && Workflow::canMakeEditorialChangesV1($motion)) {
            return true;
        }
        if ($motion->version === Workflow::STEP_V4 && Workflow::canSetResolutionV3($motion)) {
            return true;
        }
        if ($motion->version === Workflow::STEP_V5 && Workflow::canMakeEditorialChangesV5($motion)) {
            return true;
        }

        if ($motion->status === Motion::STATUS_DRAFT) {
            return $this->canEditDraftText($consultation, $motionType->getMotionPolicy(), $motion->motionSupporters);
        }

        if ($motion->textFixed) {
            return false;
        }

        if ($consultation->getSettings()->iniatorsMayEdit && $motion->iAmInitiator()) {
            if ($motionType->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS) && $motion->version === Workflow::STEP_V1) {
                if (count($motion->getVisibleAmendments()) > 0) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        }

        return false;
    }

    public function motionCanEditInitiators(Motion $motion): bool
    {
        return parent::motionCanEditText($motion);
    }

    private function iAmInitiator(IMotion $imotion): bool
    {
        // Proposers of the motions can only see their own motions
        if (is_a($imotion, Motion::class)) {
            $relevantMotionVersions = MotionNumbering::getSortedHistoryForMotion($imotion, false, true);
            foreach ($relevantMotionVersions as $relevantMotionVersion) {
                if ($relevantMotionVersion->iAmInitiator()) {
                    return true;
                }
            }
            return false;
        } else {
            return $imotion->iAmInitiator();
        }
    }

    public function iMotionIsReadable(IMotion $imotion): bool
    {
        if (!parent::iMotionIsReadable($imotion) || !$imotion->getMyConsultation()) {
            return false;
        }

        // Proposers of the motions can only see their own motions
        if ($this->iAmInitiator($imotion)) {
            return true;
        }

        if (!Module::currentUserCanSeeMotions()) {
            return false;
        }

        // No special handling for amendments
        if (!is_a($imotion, Motion::class)) {
            return true;
        }
        /** @var Motion $imotion */

        $privileges = [
            Privileges::PRIVILEGE_CONTENT_EDIT,
            Privileges::PRIVILEGE_SCREENING,
            Privileges::PRIVILEGE_MOTION_STATUS_EDIT,
            Privileges::PRIVILEGE_MOTION_SEE_UNPUBLISHED,
            Module::PRIVILEGE_DBWV_ASSIGN_TOPIC,
            Module::PRIVILEGE_DBWV_V1_EDITORIAL,
            Module::PRIVILEGE_DBWV_V4_MOVE_TO_MAIN,
        ];
        if ($imotion->getMyConsultation()->urlPath === Module::CONSULTATION_URL_BUND) {
            $privileges[] = Privileges::PRIVILEGE_CHANGE_PROPOSALS;
        }

        if (User::haveOneOfPrivileges($imotion->getMyConsultation(), $privileges, PrivilegeQueryContext::motion($imotion))) {
            return parent::iMotionIsReadable($imotion);
        } else {
            return $imotion->isVisible();
        }
    }
}
