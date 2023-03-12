<?php

namespace app\plugins\member_petitions;

use app\models\settings\Privileges;
use app\plugins\gruene_de_saml\Module;
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
        if (!$this->baseObject->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('member_petitions', 'policy_motion_denied');
    }

    public function getPermissionDeniedAmendmentMsg(): string
    {
        if (!$this->baseObject->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
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

    public function checkUser(?User $user, bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        if (!$user) {
            if ($assumeLoggedIn) {
                return true;
            } else {
                return false;
            }
        }

        if ($allowAdmins) {
            if ($user->hasPrivilege($this->consultation, Privileges::PRIVILEGE_SITE_ADMIN, null)) {
                return true;
            }
        }

        /** @var ConsultationSettings $consultationSettings */
        $consultationSettings = $this->consultation->getSettings();

        foreach ($user->getUserGroupsWithoutConsultation(Module::AUTH_KEY_GROUPS) as $userGroup) {
            if ($userGroup->externalId === Module::AUTH_KEY_GROUPS . ':' . $consultationSettings->organizationId) {
                return true;
            }
        }
        return false;
    }
}
