<?php

namespace app\models\policies;

use app\models\db\ConsultationMotionType;
use app\models\db\User;

class LoggedIn extends IPolicy
{
    /**
     * @static
     * @return int
     */
    public static function getPolicyID()
    {
        return 2;
    }

    /**
     * @static
     * @return string
     */
    public static function getPolicyName()
    {
        return \Yii::t('structure', 'policy_logged_title');
    }

    /**
     * @return bool
     */
    protected function isWriteForbidden()
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }
        if (!$this->motionType->getConsultation()->site->getSettings()->managedUserAccounts) {
            return false;
        }
        $privilege = $this->motionType->getConsultation()->getUserPrivilege($user);
        return ($privilege->privilegeCreate == 0);
    }

    /**
     * @return string
     */
    public function getOnCreateDescription()
    {
        return \Yii::t('structure', 'policy_logged_desc');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedMotionMsg()
    {
        if ($this->isWriteForbidden()) {
            return \Yii::t('structure', 'policy_specuser_motion_denied');
        }
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_logged_motion_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedAmendmentMsg()
    {
        if ($this->isWriteForbidden()) {
            return \Yii::t('structure', 'policy_specuser_amend_denied');
        }
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_logged_amend_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedSupportMsg()
    {
        if ($this->isWriteForbidden()) {
            return \Yii::t('structure', 'policy_specuser_supp_denied');
        }
        return \Yii::t('structure', 'policy_logged_supp_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedCommentMsg()
    {
        if ($this->isWriteForbidden()) {
            return \Yii::t('structure', 'policy_specuser_comm_denied');
        }
        return \Yii::t('structure', 'policy_logged_comm_denied');
    }


    /**
     * @param bool $allowAdmins
     * @param bool $assumeLoggedIn
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkCurrUser($allowAdmins = true, $assumeLoggedIn = false)
    {
        if (\Yii::$app->user->isGuest && $assumeLoggedIn) {
            return true;
        }

        if ($allowAdmins && User::getCurrentUser()) {
            foreach ($this->motionType->getConsultation()->site->admins as $admin) {
                if ($admin->id == User::getCurrentUser()->id) {
                    return true;
                }
            }
        }
        if ($this->isWriteForbidden()) {
            return false;
        }
        return (!\Yii::$app->user->isGuest);
    }
}
