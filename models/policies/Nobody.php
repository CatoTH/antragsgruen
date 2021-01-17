<?php

namespace app\models\policies;

use app\models\db\User;

class Nobody extends IPolicy
{
    public static function getPolicyID(): int
    {
        return 0;
    }

    public static function getPolicyName(): string
    {
        return \Yii::t('structure', 'policy_nobody_title');
    }

    public function getOnCreateDescription(): string
    {
        return \Yii::t('structure', 'policy_nobody_desc');
    }

    public function getPermissionDeniedMotionMsg(): string
    {
        return \Yii::t('structure', 'policy_nobody_motion_denied');
    }

    public function getPermissionDeniedAmendmentMsg(): string
    {
        return \Yii::t('structure', 'policy_nobody_amend_denied');
    }

    public function getPermissionDeniedSupportMsg(): string
    {
        return \Yii::t('structure', 'policy_nobody_supp_denied');
    }

    public function getPermissionDeniedCommentMsg(): string
    {
        return \Yii::t('structure', 'policy_nobody_comm_denied');
    }

    public function checkCurrUser(bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        if ($allowAdmins && User::getCurrentUser()) {
            if (User::havePrivilege($this->motionType->getConsultation(), User::PRIVILEGE_MOTION_EDIT)) {
                return true;
            }
        }

        return false;
    }
}
