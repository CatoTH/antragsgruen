<?php

namespace app\models\policies;

use app\components\DateTools;
use app\models\db\{ConsultationMotionType, User};

class LoggedIn extends IPolicy
{
    public static function getPolicyID(): int
    {
        return 2;
    }

    public static function getPolicyName(): string
    {
        return \Yii::t('structure', 'policy_logged_title');
    }

    protected function isWriteForbidden(): bool
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }
        if (!$this->motionType->getConsultation()->getSettings()->managedUserAccounts) {
            return false;
        }
        $privilege = $this->motionType->getConsultation()->getUserPrivilege($user);
        return ($privilege->privilegeCreate == 0);
    }

    public function getOnCreateDescription(): string
    {
        return \Yii::t('structure', 'policy_logged_desc');
    }

    public function getPermissionDeniedMotionMsg(): string
    {
        if ($this->isWriteForbidden()) {
            return \Yii::t('structure', 'policy_specuser_motion_denied');
        }
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_logged_motion_denied');
    }

    public function getPermissionDeniedAmendmentMsg(): string
    {
        if ($this->isWriteForbidden()) {
            return \Yii::t('structure', 'policy_specuser_amend_denied');
        }
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_logged_amend_denied');
    }

    public function getPermissionDeniedSupportMsg(): string
    {
        if ($this->isWriteForbidden()) {
            return \Yii::t('structure', 'policy_specuser_supp_denied');
        }
        return \Yii::t('structure', 'policy_logged_supp_denied');
    }

    public function getPermissionDeniedCommentMsg(): string
    {
        if ($this->isWriteForbidden()) {
            return \Yii::t('structure', 'policy_specuser_comm_denied');
        }
        $deadlineType = ConsultationMotionType::DEADLINE_COMMENTS;
        if (!$this->motionType->isInDeadline($deadlineType)) {
            $deadlines = DateTools::formatDeadlineRanges($this->motionType->getDeadlinesByType($deadlineType));
            return \Yii::t('structure', 'policy_deadline_over_comm') . ' ' . $deadlines;
        }
        if ($this->motionType->getCommentPolicy()->checkCurrUser(true, true)) {
            return \Yii::t('amend', 'comments_please_log_in');
        }
        return \Yii::t('structure', 'policy_logged_comm_denied');
    }

    public function checkCurrUser(bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        if (\Yii::$app->user->isGuest && $assumeLoggedIn) {
            return true;
        }

        if ($allowAdmins && User::getCurrentUser()) {
            if (User::havePrivilege($this->motionType->getMyConsultation(), User::PRIVILEGE_MOTION_EDIT)) {
                return true;
            }
        }

        if ($this->isWriteForbidden()) {
            return false;
        }
        return (!\Yii::$app->user->isGuest);
    }
}
