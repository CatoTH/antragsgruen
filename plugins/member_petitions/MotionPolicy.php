<?php

namespace app\plugins\member_petitions;

use app\models\db\ConsultationMotionType;
use app\models\db\User;
use app\models\policies\IPolicy;

class MotionPolicy extends IPolicy
{
    /**
     * @static
     * @return int
     */
    public static function getPolicyID()
    {
        return -1;
    }

    /**
     * @static
     * @return string
     */
    public static function getPolicyName()
    {
        return \Yii::t('member_petitions', 'policy_title');
    }

    /**
     * @return string
     */
    public function getOnCreateDescription()
    {
        return \Yii::t('member_petitions', 'policy_desc');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedMotionMsg()
    {
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('member_petitions', 'policy_motion_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedAmendmentMsg()
    {
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('member_petitions', 'policy_amend_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedSupportMsg()
    {
        return \Yii::t('member_petitions', 'policy_supp_denied');
    }

    /**
     * @return string
     */
    public function getPermissionDeniedCommentMsg()
    {
        return \Yii::t('member_petitions', 'policy_comm_denied');
    }


    /**
     * @param bool $allowAdmins
     * @param bool $assumeLoggedIn
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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

        if ($allowAdmins) {
            foreach ($this->motionType->getConsultation()->site->admins as $admin) {
                if ($admin->id == User::getCurrentUser()->id) {
                    return true;
                }
            }
        }

        /** @var ConsultationSettings $consultationSettings */
        $consultationSettings = $this->motionType->getMyConsultation()->getSettings();

        return in_array($consultationSettings->organizationId, $user->getMyOrganizationIds());
    }
}
