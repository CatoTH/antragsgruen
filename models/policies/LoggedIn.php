<?php

namespace app\models\policies;

use app\components\DateTools;
use app\models\settings\Privileges;
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
        if (!$this->consultation->getSettings()->managedUserAccounts) {
            return false;
        }

        // It's forbidden if user accounts are managed and the user is NOT in any consultation-specific user group
        $userGroups = $user->getUserGroupsForConsultation($this->consultation);
        return count($userGroups) === 0;
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
        if (!$this->baseObject->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_logged_motion_denied');
    }

    public function getPermissionDeniedAmendmentMsg(): string
    {
        if ($this->isWriteForbidden()) {
            return \Yii::t('structure', 'policy_specuser_amend_denied');
        }
        if (!$this->baseObject->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
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
        if (!$this->baseObject->isInDeadline($deadlineType)) {
            $deadlines = DateTools::formatDeadlineRanges($this->baseObject->getDeadlinesByType($deadlineType));
            return \Yii::t('structure', 'policy_deadline_over_comm') . ' ' . $deadlines;
        }
        $baseObject = $this->baseObject;
        if (is_a($baseObject, ConsultationMotionType::class) && $baseObject->getCommentPolicy()->checkCurrUser(true, true)) {
            return \Yii::t('amend', 'comments_please_log_in');
        }
        return \Yii::t('structure', 'policy_logged_comm_denied');
    }

    public function checkUser(?User $user, bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        if ($user === null) {
            // If the user is not logged into, permission is usually not granted. If $assumeLoggedIn is true,
            // then permission is granted (to lead the user to a login form)
            return $assumeLoggedIn;
        }

        if ($allowAdmins && $user->hasPrivilege($this->consultation, Privileges::PRIVILEGE_MOTION_STATUS_EDIT, null)) {
            return true;
        }

        if ($this->isWriteForbidden()) {
            return false;
        }
        return true;
    }
}
