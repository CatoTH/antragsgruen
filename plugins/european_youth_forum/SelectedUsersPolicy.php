<?php

namespace app\plugins\european_youth_forum;

use app\models\db\{ConsultationMotionType, ConsultationUserGroup, User};
use app\models\policies\IPolicy;

class SelectedUsersPolicy extends IPolicy
{
    public static function getPolicyID(): int
    {
        return -2;
    }

    public static function getPolicyName(): string
    {
        return 'Selected users (hardcoded)';
    }

    public function getOnCreateDescription(): string
    {
        return 'Selected users are allowed';
    }

    public function getPermissionDeniedMotionMsg(): string
    {
        if (!$this->baseObject->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return 'Only selected users are allowed to create a motion';
    }

    public function getPermissionDeniedAmendmentMsg(): string
    {
        if (!$this->baseObject->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return 'Only selected users are allowed to create an amendment';
    }

    public function getPermissionDeniedSupportMsg(): string
    {
        return 'Only selected users are allowed to support';
    }

    public function getPermissionDeniedCommentMsg(): string
    {
        return 'Only selected users are allowed to comment';
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

        if ($allowAdmins && $this->consultation->havePrivilege(ConsultationUserGroup::PRIVILEGE_SITE_ADMIN)) {
            return true;
        }

        if (!$user->email || !$user->emailConfirmed) {
            return false;
        }

        // @TODO Create a UI for this and switch to a user-flag in the database
        $forbiddenEmails = array_map('strtolower', explode("\n", trim(file_get_contents(__DIR__ . '/../../config/eyf-forbidden-emails.txt'))));
        return !in_array(strtolower($user->email), $forbiddenEmails);
    }
}
