<?php

declare(strict_types=1);

namespace app\plugins\member_petitions;

use app\models\db\{Motion, User};
use app\models\settings\{PrivilegeQueryContext, Privileges};

class Permissions extends \app\models\settings\Permissions
{
    /**
     * @throws \Exception
     */
    public function motionCanMergeAmendments(Motion $motion): bool
    {
        $replacedByMotions = array_filter($motion->replacedByMotions, function (Motion $motion) {
            $draftStatuses = [
                Motion::STATUS_DELETED,
                Motion::STATUS_DRAFT,
                Motion::STATUS_MERGING_DRAFT_PUBLIC,
                Motion::STATUS_MERGING_DRAFT_PRIVATE,
                Motion::STATUS_WITHDRAWN_INVISIBLE,
                Motion::STATUS_PROPOSED_MODIFIED_AMENDMENT,
                Motion::STATUS_PROPOSED_MODIFIED_MOTION,
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

        if ($motion->iAmInitiator() || User::havePrivilege($motion->getMyConsultation(), Privileges::PRIVILEGE_MOTION_STATUS_EDIT, PrivilegeQueryContext::motion($motion))) {
            return true;
        }

        return false;
    }
}
