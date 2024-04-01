<?php

namespace app\models\policies;

use app\components\DateTools;
use app\models\settings\Privileges;
use app\models\db\{Consultation, ConsultationMotionType, ConsultationUserGroup, IHasPolicies, User};

class UserGroups extends IPolicy
{
    /** @var ConsultationUserGroup[] */
    private array $groups = [];

    public function __construct(Consultation $consultation, IHasPolicies $baseObject, ?array $data)
    {
        parent::__construct($consultation, $baseObject, $data);

        $userGroupIds = $data['userGroups'] ?? [];
        foreach ($consultation->getAllAvailableUserGroups($userGroupIds, true) as $group) {
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

    public function checkUser(?User $user, bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        if ($user === null) {
            // If the user is not logged into, permission is usually not granted. If $assumeLoggedIn is true,
            // then permission is granted (to lead the user to a login form)
            return $assumeLoggedIn;
        }

        if ($allowAdmins && $user->hasPrivilege($this->consultation, Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null)) {
            return true;
        }

        foreach ($this->groups as $group) {
            if ($group->hasUser($user)) {
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

    /**
     * @return ConsultationUserGroup[]
     */
    public function getAllowedUserGroups(): array {
        return $this->groups;
    }

    /**
     * @param ConsultationUserGroup[] $groups
     */
    public function setAllowedUserGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    public function getEligibilityByGroup(): ?array
    {
        $groups = [];

        foreach ($this->groups as $group) {
            $groups[] = EligibilityByGroup::fromUserGroup($group, $this->consultation);
        }

        return $groups;
    }

    public function getApiObject(): array
    {
        $groupNames = array_map(function (ConsultationUserGroup $group): string {
            return $group->getNormalizedTitle();
        }, $this->groups);

        return [
            'id' => static::getPolicyID(),
            'user_groups' => array_values(array_map(function(ConsultationUserGroup $group): int {
                return $group->id;
            }, $this->groups)),
            'description' => implode(', ', $groupNames),
        ];
    }

    public function serializeInstanceForDb(): string
    {
        return json_encode([
            'id' => static::getPolicyID(),
            'userGroups' => array_values(array_map(function(ConsultationUserGroup $group): int {
                return $group->id;
            }, $this->groups)),
        ], JSON_THROW_ON_ERROR);
    }
}
