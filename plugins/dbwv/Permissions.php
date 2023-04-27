<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\models\db\ConsultationMotionType;
use app\models\db\Motion;
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
}
