<?php

namespace app\models\policies;

use app\components\DateTools;
use app\models\db\ConsultationMotionType;
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
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_ww_motion_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedAmendmentMsg()
    {
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
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
        $deadlineType = ConsultationMotionType::DEADLINE_COMMENTS;
        if (!$this->motionType->isInDeadline($deadlineType)) {
            $deadlines = DateTools::formatDeadlineRanges($this->motionType->getDeadlines($deadlineType));
            return \Yii::t('structure', 'policy_deadline_over_comm') . ' ' . $deadlines;
        }
        if ($this->motionType->getCommentPolicy()->checkCurrUser(true, true)) {
            return \Yii::t('amend', 'comments_please_log_in');
        }
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
        if ($allowAdmins && $user->hasPrivilege($this->motionType->getConsultation(), User::PRIVILEGE_ANY)) {
            return true;
        }
        return $user->isWurzelwerkUser();
    }
}
