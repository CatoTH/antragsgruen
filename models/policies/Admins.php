<?php

namespace app\models\policies;

use app\components\DateTools;
use app\models\db\ConsultationMotionType;
use app\models\db\User;

class Admins extends IPolicy
{
    /**
     * @static
     * @return int
     */
    public static function getPolicyID()
    {
        return 3;
    }

    /**
     * @static
     * @return string
     */
    public static function getPolicyName()
    {
        return \Yii::t('structure', 'policy_admin_title');
    }

    /**
     * @return string
     */
    public function getOnCreateDescription()
    {
        return \Yii::t('structure', 'policy_admin_desc');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedMotionMsg()
    {
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_admin_motion_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedAmendmentMsg()
    {
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_admin_amend_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedSupportMsg()
    {
        return \Yii::t('structure', 'policy_admin_supp_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedCommentMsg()
    {
        $deadlineType = ConsultationMotionType::DEADLINE_COMMENTS;
        if (!$this->motionType->isInDeadline($deadlineType)) {
            $deadlines = DateTools::formatDeadlineRanges($this->motionType->getDeadlinesByType($deadlineType));
            return \Yii::t('structure', 'policy_deadline_over_comm') . ' ' . $deadlines;
        }
        return \Yii::t('structure', 'policy_admin_comm_denied');
    }

    /**
     * @param bool $allowAdmins
     * @param bool $assumeLoggedIn
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkCurrUser($allowAdmins = true, $assumeLoggedIn = false)
    {
        return User::havePrivilege($this->motionType->getConsultation(), User::PRIVILEGE_MOTION_EDIT);
    }
}
