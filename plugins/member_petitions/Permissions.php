<?php

namespace app\plugins\member_petitions;

use app\models\db\{Motion, User};

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
                Motion::STATUS_DELETED,
                Motion::STATUS_DRAFT,
                Motion::STATUS_MERGING_DRAFT_PUBLIC,
                Motion::STATUS_MERGING_DRAFT_PRIVATE,
                Motion::STATUS_WITHDRAWN_INVISIBLE,
                Motion::STATUS_PROPOSED_MODIFIED_AMENDMENT,
                Motion::STATUS_INLINE_REPLY,
                Motion::STATUS_DRAFT_ADMIN,
            ];
            return !in_array($motion->status, $draftStatuses);
        });
        if (count($replacedByMotions) > 0) {
            return false;
        }

        if (Tools::isPetitionsActive($motion->getMyConsultation())) {
            if (!Tools::isDiscussionUntilOver($motion)) {
                return false;
            }
        }

        if ($motion->iAmInitiator() || User::havePrivilege($motion->getMyConsultation(), User::PRIVILEGE_MOTION_EDIT)) {
            return true;
        }

        return false;
    }
}
