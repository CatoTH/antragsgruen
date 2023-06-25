<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\components\MotionNumbering;
use app\models\db\ConsultationMotionType;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\db\User;
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

    public function iMotionIsReadable(IMotion $imotion): bool
    {
        // Admins, delegates
        if (Module::currentUserCanSeeMotions()) {
            return parent::iMotionIsReadable($imotion);
        }

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
}
