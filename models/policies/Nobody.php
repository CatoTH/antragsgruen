<?php

namespace app\models\policies;

use app\models\db\User;

class Nobody extends IPolicy
{
    /**
     * @static
     * @return int
     */
    public static function getPolicyID()
    {
        return 0;
    }

    /**
     * @static
     * @return string
     */
    public static function getPolicyName()
    {
        return \Yii::t('structure', 'policy_nobody_title');
    }

    /**
     * @return string
     */
    public function getOnCreateDescription()
    {
        return \Yii::t('structure', 'policy_nobody_desc');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedMotionMsg()
    {
        return \Yii::t('structure', 'policy_nobody_motion_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedAmendmentMsg()
    {
        return \Yii::t('structure', 'policy_nobody_amend_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedSupportMsg()
    {
        return \Yii::t('structure', 'policy_nobody_supp_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedCommentMsg()
    {
        return \Yii::t('structure', 'policy_nobody_comm_denied');
    }

    /**
     * @param bool $allowAdmins
     * @param bool $assumeLoggedIn
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkCurrUser($allowAdmins = true, $assumeLoggedIn = false)
    {
        if ($allowAdmins && User::getCurrentUser()) {
            if (User::havePrivilege($this->motionType->getMyConsultation(), User::PRIVILEGE_MOTION_EDIT)) {
                return true;
            }
        }

        return false;
    }
}
