<?php

namespace app\models\policies;

use app\models\db\User;

class Wurzelwerk extends IPolicy
{
    /**
     * @static
     * @return int
     */
    public static function getPolicyID()
    {
        return 4;
    }

    /**
     * @static
     * @return string
     */
    public static function getPolicyName()
    {
        return \Yii::t('structure', 'policy_ww_title');
    }

    /**
     * @return string
     */
    public function getOnCreateDescription()
    {
        return \Yii::t('structure', 'policy_ww_desc');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedMotionMsg()
    {
        return \Yii::t('structure', 'policy_ww_motion_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedAmendmentMsg()
    {
        return \Yii::t('structure', 'policy_ww_amend_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedSupportMsg()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedCommentMsg()
    {
        return \Yii::t('structure', 'policy_ww_comm_denied');
    }

    /**
     * @param bool $allowAdmins
     * @param bool $assumeLoggedIn
     * @return bool
     */
    public function checkCurrUser($allowAdmins = true, $assumeLoggedIn = false)
    {
        $user = User::getCurrentUser();
        if (!$user) {
            if ($assumeLoggedIn) {
                return true;
            } else {
                return false;
            }
        }
        if ($allowAdmins && $user->hasPrivilege($this->motionType->consultation, User::PRIVILEGE_ANY)) {
            return true;
        }
        return $user->isWurzelwerkUser();
    }
}
