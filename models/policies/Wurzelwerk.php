<?php

namespace app\models\policies;

use app\components\DateTools;
use app\models\db\{ConsultationMotionType, User};

class Wurzelwerk extends IPolicy
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
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_ww_motion_denied');
    }

    public function getPermissionDeniedAmendmentMsg(): string
    {
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
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
        if (!$this->motionType->isInDeadline($deadlineType)) {
            $deadlines = DateTools::formatDeadlineRanges($this->motionType->getDeadlinesByType($deadlineType));
            return \Yii::t('structure', 'policy_deadline_over_comm') . ' ' . $deadlines;
        }
        if ($this->motionType->getCommentPolicy()->checkCurrUser(true, true)) {
            return \Yii::t('amend', 'comments_please_log_in');
        }
        return \Yii::t('structure', 'policy_ww_comm_denied');
    }

    public function checkCurrUser(bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        $user = User::getCurrentUser();
        if (!$user) {
            if ($assumeLoggedIn) {
                return true;
            } else {
                return false;
            }
        }
        if ($allowAdmins && $user->hasPrivilege($this->motionType->getConsultation(), User::PRIVILEGE_MOTION_EDIT)) {
            return true;
        }
        return $user->isWurzelwerkUser();
    }
}
