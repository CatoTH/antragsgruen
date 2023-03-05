<?php

namespace app\models\policies;

use app\components\DateTools;
use app\models\settings\Privileges;
use app\models\db\{ConsultationMotionType, User};

class GruenesNetz extends IPolicy
{
    public static function getPolicyID(): int
    {
        return 4;
    }

    public static function getPolicyName(): string
    {
        return \Yii::t('structure', 'policy_ww_title');
    }

    public function getOnCreateDescription(): string
    {
        return \Yii::t('structure', 'policy_ww_desc');
    }

    public function getPermissionDeniedMotionMsg(): string
    {
        if (!$this->baseObject->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_ww_motion_denied');
    }

    public function getPermissionDeniedAmendmentMsg(): string
    {
        if (!$this->baseObject->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_ww_amend_denied');
    }

    public function getPermissionDeniedSupportMsg(): string
    {
        return '';
    }

    public function getPermissionDeniedCommentMsg(): string
    {
        $deadlineType = ConsultationMotionType::DEADLINE_COMMENTS;
        if (!$this->baseObject->isInDeadline($deadlineType)) {
            $deadlines = DateTools::formatDeadlineRanges($this->baseObject->getDeadlinesByType($deadlineType));
            return \Yii::t('structure', 'policy_deadline_over_comm') . ' ' . $deadlines;
        }
        $baseObject = $this->baseObject;
        if (is_a($baseObject, ConsultationMotionType::class) && $baseObject->getCommentPolicy()->checkCurrUser(true, true)) {
            return \Yii::t('amend', 'comments_please_log_in');
        }
        return \Yii::t('structure', 'policy_ww_comm_denied');
    }

    public function checkUser(?User $user, bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        if (!$user) {
            if ($assumeLoggedIn) {
                return true;
            } else {
                return false;
            }
        }
        if ($allowAdmins && $user->hasPrivilege($this->consultation, Privileges::PRIVILEGE_MOTION_STATUS_EDIT, null)) {
            return true;
        }
        return $user->isGruenesNetzUser();
    }
}
