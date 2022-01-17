<?php

namespace app\models\policies;

use app\components\DateTools;
use app\models\db\{Consultation, ConsultationMotionType, ConsultationUserGroup, IHasPolicies, User};

class UserGroups extends IPolicy
{
    /** @var ConsultationUserGroup[] */
    private $groups = [];

    public function __construct(Consultation $consultation, IHasPolicies $baseObject, ?array $data)
    {
        parent::__construct($consultation, $baseObject, $data);

        $userGroupIds = $data['userGroups'] ?? [];
        foreach ($consultation->getAllAvailableUserGroups() as $group) {
            if (in_array($group->id, $userGroupIds)) {
                $this->groups[] = $group;
            }
        }
    }

    public static function getPolicyID(): int
    {
        return IPolicy::POLICY_USER_GROUPS;
    }

    public static function getPolicyName(): string
    {
        return \Yii::t('structure', 'policy_groups_title');
    }

    public function getOnCreateDescription(): string
    {
        $groupNames = array_map(function (ConsultationUserGroup $group): string {
            return $group->getNormalizedTitle();
        }, $this->groups);
        return \Yii::t('structure', 'policy_groups_desc') . ': ' . implode(', ', $groupNames);
    }

    public function getPermissionDeniedMotionMsg(): string
    {
        if (!$this->baseObject->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_groups_motion_denied');
    }

    public function getPermissionDeniedAmendmentMsg(): string
    {
        if (!$this->baseObject->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return \Yii::t('structure', 'policy_groups_amend_denied');
    }

    public function getPermissionDeniedSupportMsg(): string
    {
        return \Yii::t('structure', 'policy_groups_supp_denied');
    }

    public function getPermissionDeniedCommentMsg(): string
    {
        $deadlineType = ConsultationMotionType::DEADLINE_COMMENTS;
        if (!$this->baseObject->isInDeadline($deadlineType)) {
            $deadlines = DateTools::formatDeadlineRanges($this->baseObject->getDeadlinesByType($deadlineType));
            return \Yii::t('structure', 'policy_deadline_over_comm') . ' ' . $deadlines;
        }
        return \Yii::t('structure', 'policy_groups_comm_denied');
    }

    public function checkCurrUser(bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        $currentUser = User::getCurrentUser();
        if ($currentUser === null) {
            // If the user is not logged into, permission is usually not granted. If $assumeLoggedIn is true,
            // then permission is granted (to lead the user to a login form)
            return $assumeLoggedIn;
        }

        if ($allowAdmins && User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CONSULTATION_SETTINGS)) {
            return true;
        }

        foreach ($this->groups as $group) {
            if ($group->hasUser($currentUser)) {
                return true;
            }
        }
        return false;
    }

    public function allowsUserGroup(ConsultationUserGroup $group): bool
    {
        foreach ($this->groups as $_group) {
            if ($_group->id === $group->id) {
                return true;
            }
        }
        return false;
    }

    public function setAllowedUserGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    public function serializeInstanceForDb(): string
    {
        return json_encode([
            'id' => static::getPolicyID(),
            'userGroups' => array_map(function(ConsultationUserGroup $group): int {
                return $group->id;
            }, $this->groups),
        ]);
    }
}
