<?php

namespace app\plugins\member_petitions;

use app\models\db\{ConsultationMotionType, User};
use app\models\policies\IPolicy;

class MotionPolicy extends IPolicy
{
    public static function getPolicyID(): int
    {
        return -1;
    }

    public static function getPolicyName(): string
    {
        return \Yii::t('member_petitions', 'policy_title');
    }

    public function getOnCreateDescription(): string
    {
        return \Yii::t('member_petitions', 'policy_desc');
    }

    public function getPermissionDeniedMotionMsg(): string
    {
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('member_petitions', 'policy_motion_denied');
    }

    public function getPermissionDeniedAmendmentMsg(): string
    {
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('member_petitions', 'policy_amend_denied');
    }

    public function getPermissionDeniedSupportMsg(): string
    {
        return \Yii::t('member_petitions', 'policy_supp_denied');
    }

    public function getPermissionDeniedCommentMsg(): string
    {
        return \Yii::t('member_petitions', 'policy_comm_denied');
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

        if ($allowAdmins) {
            foreach ($this->motionType->getConsultation()->site->admins as $admin) {
                if ($admin->id == User::getCurrentUser()->id) {
                    return true;
                }
            }
        }

        /** @var ConsultationSettings $consultationSettings */
        $consultationSettings = $this->motionType->getConsultation()->getSettings();

        return in_array($consultationSettings->organizationId, $user->getMyOrganizationIds());
    }
}
