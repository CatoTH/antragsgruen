<?php

namespace app\models\policies;

class All extends IPolicy
{
    /**
     * @static
     * @return int
     */
    public static function getPolicyID()
    {
        return 1;
    }

    /**
     * @static
     * @return string
     */
    public static function getPolicyName()
    {
        return \Yii::t('structure', 'policy_all_title');
    }

    /**
     * @return string
     */
    public function getOnCreateDescription()
    {
        return \Yii::t('structure', 'policy_all_desc');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedMotionMsg()
    {
        if ($this->motionType->motionDeadlineIsOver()) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return '';
    }

    /**
     * @return string
     */
    public function getPermissionDeniedAmendmentMsg()
    {
        if ($this->motionType->motionDeadlineIsOver()) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return '';
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
        return '';
    }

    /**
     * @param bool $allowAdmins
     * @param bool $assumeLoggedIn
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkCurrUser($allowAdmins = true, $assumeLoggedIn = false)
    {
        return true;
    }
}
