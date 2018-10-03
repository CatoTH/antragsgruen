<?php

namespace app\plugins\memberPetitions;

use app\models\db\Motion;
use app\models\db\User;

class Permissions extends \app\models\siteSpecificBehavior\Permissions
{
    /**
     * @param Motion $motion
     * @return bool
     * @throws \Exception
     */
    public function motionCanMergeAmendments($motion)
    {
        $replacedByMotions = array_filter($motion->replacedByMotions, function (Motion $motion) {
            $draftStatuses = [
                Motion::STATUS_DRAFT,
                Motion::STATUS_MERGING_DRAFT_PUBLIC,
                Motion::STATUS_MERGING_DRAFT_PRIVATE
            ];
            return !in_array($motion->status, $draftStatuses);
        });
        if (count($replacedByMotions) > 0) {
            return false;
        }

        if (!Tools::isDiscussionUntilOver($motion)) {
            return false;
        }

        if ($motion->iAmInitiator() || User::havePrivilege($motion->getMyConsultation(), User::PRIVILEGE_MOTION_EDIT)) {
            return true;
        }

        return false;
    }
}
