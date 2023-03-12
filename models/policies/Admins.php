<?php

namespace app\models\policies;

use app\components\DateTools;
use app\models\settings\Privileges;
use app\models\db\{ConsultationMotionType, User};

class Admins extends IPolicy
{
    public static function getPolicyID(): int
    {
        return 3;
    }

    public static function getPolicyName(): string
    {
        return \Yii::t('structure', 'policy_admin_title');
    }

    public function getOnCreateDescription(): string
    {
        return \Yii::t('structure', 'policy_admin_desc');
    }

    public function getPermissionDeniedMotionMsg(): string
    {
        if (!$this->baseObject->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_admin_motion_denied');
    }

    public function getPermissionDeniedAmendmentMsg(): string
    {
        if (!$this->baseObject->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_admin_amend_denied');
    }

    public function getPermissionDeniedSupportMsg(): string
    {
        return \Yii::t('structure', 'policy_admin_supp_denied');
    }

    public function getPermissionDeniedCommentMsg(): string
    {
        $deadlineType = ConsultationMotionType::DEADLINE_COMMENTS;
        if (!$this->baseObject->isInDeadline($deadlineType)) {
            $deadlines = DateTools::formatDeadlineRanges($this->baseObject->getDeadlinesByType($deadlineType));
            return \Yii::t('structure', 'policy_deadline_over_comm') . ' ' . $deadlines;
        }
        return \Yii::t('structure', 'policy_admin_comm_denied');
    }

    public function checkUser(?User $user, bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        if (!$user) {
            return false;
        }
        return $user->hasPrivilege($this->consultation, Privileges::PRIVILEGE_MOTION_STATUS_EDIT, null);
    }
}
